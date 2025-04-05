/**
 * CarFuse Forms - File Upload Module
 * Handles file uploads with progress tracking and validation
 */

(function() {
  // Namespaces
  const CarFuse = window.CarFuse || {};
  if (!CarFuse.forms) CarFuse.forms = {};
  
  // Upload module
  const uploads = {
    // Configuration
    config: {
      maxFileSize: 5 * 1024 * 1024, // 5MB default
      allowedTypes: [], // Empty means all types allowed
      multipleFiles: false,
      chunkSize: 1024 * 1024, // 1MB chunks for large files
      uploadUrl: '/api/upload',
      csrfTokenName: '_token',
      autoUpload: false,
      dragDropEnabled: true,
      showPreview: true,
      previewMaxSize: 2 * 1024 * 1024, // Max size for preview generation
      previewMaxDimension: 200, // Max width/height for image previews
      imageCompression: false,
      compressionQuality: 0.7
    },
    
    /**
     * Initialize upload module
     * @param {Object} options Configuration options
     * @returns {Promise} Promise that resolves when initialization is complete
     */
    init(options = {}) {
      // Apply custom options
      Object.assign(this.config, options);
      
      return Promise.resolve(this);
    },
    
    /**
     * Create a file uploader for a specific input
     * @param {HTMLElement|string} input File input element or selector
     * @param {Object} options Upload options
     * @returns {Object} Uploader instance
     */
    createUploader(input, options = {}) {
      // Get input element if string provided
      const inputElement = typeof input === 'string' 
        ? document.querySelector(input) 
        : input;
      
      if (!inputElement) {
        throw new Error(`File input not found: ${input}`);
      }
      
      // Check if the input is a file input
      if (inputElement.type !== 'file') {
        throw new Error('Input element must be of type "file"');
      }
      
      // Local options (combine global config with input-specific options)
      const config = { ...this.config, ...options };
      
      // Set multiple attribute if needed
      if (config.multipleFiles) {
        inputElement.setAttribute('multiple', '');
      }
      
      // Set accept attribute if specified
      if (config.allowedTypes && config.allowedTypes.length > 0) {
        inputElement.setAttribute('accept', config.allowedTypes.join(','));
      }
      
      // Uploader methods
      const uploader = {
        input: inputElement,
        config,
        files: [],
        uploading: false,
        uploaded: false,
        xhr: null,
        
        /**
         * Initialize the uploader
         * @returns {Object} This uploader instance
         */
        init() {
          // Create UI container
          this._createUi();
          
          // Set up event listeners
          this._setupEventListeners();
          
          return this;
        },
        
        /**
         * Create upload UI elements
         * @private
         */
        _createUi() {
          // Create container
          this.container = document.createElement('div');
          this.container.className = 'cf-file-uploader';
          
          // Hide original input
          this.input.style.display = 'none';
          
          // Insert container after input
          this.input.parentNode.insertBefore(this.container, this.input.nextSibling);
          
          // Create drop zone if enabled
          if (this.config.dragDropEnabled) {
            this.dropZone = document.createElement('div');
            this.dropZone.className = 'cf-drop-zone p-4 border-2 border-dashed border-gray-300 rounded-md text-center';
            this.dropZone.innerHTML = `
              <div class="cf-drop-icon mb-2">
                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
              </div>
              <p class="cf-drop-text text-gray-600">Drag & drop files here or</p>
              <button type="button" class="cf-browse-button mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                Browse Files
              </button>
            `;
            
            this.container.appendChild(this.dropZone);
            
            // Add click handler to browse button
            const browseButton = this.dropZone.querySelector('.cf-browse-button');
            browseButton.addEventListener('click', () => {
              this.input.click();
            });
          }
          
          // Create file list container
          this.fileList = document.createElement('div');
          this.fileList.className = 'cf-file-list mt-4';
          this.container.appendChild(this.fileList);
          
          // Create upload progress container
          this.progressContainer = document.createElement('div');
          this.progressContainer.className = 'cf-upload-progress mt-4 hidden';
          this.progressContainer.innerHTML = `
            <div class="flex justify-between items-center mb-1">
              <span class="cf-progress-text text-sm text-gray-600">Uploading...</span>
              <span class="cf-progress-percentage text-sm text-gray-600">0%</span>
            </div>
            <div class="cf-progress-bar w-full bg-gray-200 rounded-full h-2.5">
              <div class="cf-progress-bar-fill bg-primary-600 h-2.5 rounded-full" style="width: 0%"></div>
            </div>
          `;
          this.container.appendChild(this.progressContainer);
          
          // If not auto-upload, create upload button
          if (!this.config.autoUpload) {
            this.uploadButton = document.createElement('button');
            this.uploadButton.type = 'button';
            this.uploadButton.className = 'cf-upload-button mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 hidden';
            this.uploadButton.textContent = 'Upload Files';
            this.uploadButton.addEventListener('click', () => {
              this.upload();
            });
            this.container.appendChild(this.uploadButton);
          }
        },
        
        /**
         * Set up event listeners
         * @private
         */
        _setupEventListeners() {
          // File selection
          this.input.addEventListener('change', () => {
            this._handleFileSelection();
          });
          
          // Drag and drop
          if (this.config.dragDropEnabled && this.dropZone) {
            this.dropZone.addEventListener('dragover', e => {
              e.preventDefault();
              e.stopPropagation();
              this.dropZone.classList.add('border-primary-500', 'bg-primary-50');
            });
            
            this.dropZone.addEventListener('dragleave', e => {
              e.preventDefault();
              e.stopPropagation();
              this.dropZone.classList.remove('border-primary-500', 'bg-primary-50');
            });
            
            this.dropZone.addEventListener('drop', e => {
              e.preventDefault();
              e.stopPropagation();
              this.dropZone.classList.remove('border-primary-500', 'bg-primary-50');
              
              if (e.dataTransfer.files.length > 0) {
                this._handleFiles(Array.from(e.dataTransfer.files));
              }
            });
          }
        },
        
        /**
         * Handle file selection from input
         * @private
         */
        _handleFileSelection() {
          if (this.input.files.length > 0) {
            this._handleFiles(Array.from(this.input.files));
          }
        },
        
        /**
         * Process selected files
         * @param {File[]} fileList Array of files
         * @private
         */
        _handleFiles(fileList) {
          // Clear previous files if not multiple
          if (!this.config.multipleFiles) {
            this.files = [];
            this.fileList.innerHTML = '';
          }
          
          // Process each file
          const validFiles = [];
          
          fileList.forEach(file => {
            // Check file size
            if (this.config.maxFileSize > 0 && file.size > this.config.maxFileSize) {
              this._showError(`File ${file.name} exceeds maximum size of ${this._formatSize(this.config.maxFileSize)}`);
              return;
            }
            
            // Check file type
            if (this.config.allowedTypes.length > 0) {
              const fileType = file.type || this._getFileExtension(file.name);
              if (!this._isAllowedFileType(fileType)) {
                this._showError(`File type ${fileType} is not allowed`);
                return;
              }
            }
            
            validFiles.push(file);
          });
          
          // Add valid files
          validFiles.forEach(file => {
            this._addFile(file);
          });
          
          // Show upload button if needed
          if (!this.config.autoUpload && this.uploadButton && this.files.length > 0) {
            this.uploadButton.classList.remove('hidden');
          }
          
          // Auto upload if configured
          if (this.config.autoUpload && this.files.length > 0) {
            this.upload();
          }
          
          // Dispatch event
          this._dispatchEvent('cf:files:added', { files: this.files });
        },
        
        /**
         * Add file to collection and UI
         * @param {File} file File to add
         * @private
         */
        _addFile(file) {
          // Add to files array with unique ID
          const fileId = Date.now() + '_' + Math.random().toString(36).substring(2, 9);
          const fileObj = {
            id: fileId,
            file,
            name: file.name,
            size: file.size,
            type: file.type || this._getFileExtension(file.name),
            progress: 0,
            uploaded: false,
            error: null
          };
          
          this.files.push(fileObj);
          
          // Create file item in UI
          this._createFileItem(fileObj);
          
          // Generate preview if enabled
          if (this.config.showPreview) {
            this._generatePreview(fileObj);
          }
        },
        
        /**
         * Create file item in UI
         * @param {Object} fileObj File object
         * @private
         */
        _createFileItem(fileObj) {
          const item = document.createElement('div');
          item.className = 'cf-file-item flex items-center justify-between p-2 border-b border-gray-200';
          item.dataset.fileId = fileObj.id;
          
          const fileInfo = document.createElement('div');
          fileInfo.className = 'cf-file-info flex items-center';
          
          // Preview placeholder
          const previewContainer = document.createElement('div');
          previewContainer.className = 'cf-file-preview w-10 h-10 mr-3 bg-gray-200 flex items-center justify-center rounded';
          previewContainer.innerHTML = this._getFileTypeIcon(fileObj.type);
          fileObj.previewContainer = previewContainer;
          
          // File details
          const fileDetails = document.createElement('div');
          fileDetails.className = 'cf-file-details';
          fileDetails.innerHTML = `
            <div class="cf-file-name text-sm font-medium">${fileObj.name}</div>
            <div class="cf-file-size text-xs text-gray-500">${this._formatSize(fileObj.size)}</div>
            <div class="cf-file-progress text-xs text-primary-600 hidden">0%</div>
          `;
          
          // Actions
          const actions = document.createElement('div');
          actions.className = 'cf-file-actions';
          
          const removeButton = document.createElement('button');
          removeButton.type = 'button';
          removeButton.className = 'cf-file-remove text-red-500 hover:text-red-700';
          removeButton.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          `;
          
          removeButton.addEventListener('click', () => {
            this.removeFile(fileObj.id);
          });
          
          // Assemble
          fileInfo.appendChild(previewContainer);
          fileInfo.appendChild(fileDetails);
          actions.appendChild(removeButton);
          item.appendChild(fileInfo);
          item.appendChild(actions);
          
          // Add to file list
          this.fileList.appendChild(item);
          
          // Store reference in file object
          fileObj.element = item;
        },
        
        /**
         * Generate preview for image files
         * @param {Object} fileObj File object
         * @private
         */
        _generatePreview(fileObj) {
          // Only for images and if below preview max size
          if (!fileObj.type.startsWith('image/') || fileObj.size > this.config.previewMaxSize) {
            return;
          }
          
          const reader = new FileReader();
          
          reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
              // Create canvas for resizing
              const canvas = document.createElement('canvas');
              let width = img.width;
              let height = img.height;
              
              // Calculate dimensions to maintain aspect ratio
              if (width > height) {
                if (width > this.config.previewMaxDimension) {
                  height *= this.config.previewMaxDimension / width;
                  width = this.config.previewMaxDimension;
                }
              } else {
                if (height > this.config.previewMaxDimension) {
                  width *= this.config.previewMaxDimension / height;
                  height = this.config.previewMaxDimension;
                }
              }
              
              canvas.width = width;
              canvas.height = height;
              
              // Draw image on canvas
              const ctx = canvas.getContext('2d');
              ctx.drawImage(img, 0, 0, width, height);
              
              // Update preview
              const dataUrl = canvas.toDataURL(fileObj.type);
              
              if (fileObj.previewContainer) {
                fileObj.previewContainer.innerHTML = `<img src="${dataUrl}" class="w-full h-full object-cover rounded" alt="${fileObj.name}">`;
              }
              
              // Compress image if enabled
              if (this.config.imageCompression) {
                this._compressImage(fileObj, img);
              }
            };
            
            img.src = e.target.result;
          };
          
          reader.readAsDataURL(fileObj.file);
        },
        
        /**
         * Upload all files
         * @returns {Promise} Promise that resolves with upload results
         */
        upload() {
          if (this.uploading) {
            return Promise.reject(new Error('Upload already in progress'));
          }
          
          if (this.files.length === 0) {
            return Promise.resolve([]);
          }
          
          this.uploading = true;
          
          // Show progress container
          this.progressContainer.classList.remove('hidden');
          
          // Prepare FormData with all files
          const formData = new FormData();
          
          // Use field name from input or config
          const fieldName = this.config.fieldName || this.input.name || 'file';
          
          // Add files
          this.files.forEach(fileObj => {
            if (this.config.multipleFiles) {
              formData.append(`${fieldName}[]`, fileObj.file);
            } else {
              formData.append(fieldName, fileObj.file);
            }
          });
          
          // Add CSRF token
          this._addCsrfToken(formData);
          
          // Add any additional data
          if (this.config.additionalData) {
            Object.entries(this.config.additionalData).forEach(([key, value]) => {
              formData.append(key, value);
            });
          }
          
          // Create XHR request
          const xhr = new XMLHttpRequest();
          this.xhr = xhr;
          
          // Track upload progress
          xhr.upload.addEventListener('progress', e => {
            if (e.lengthComputable) {
              const progress = Math.round((e.loaded / e.total) * 100);
              this._updateProgress(progress);
            }
          });
          
          return new Promise((resolve, reject) => {
            // Set up completion handler
            xhr.addEventListener('load', () => {
              if (xhr.status >= 200 && xhr.status < 300) {
                try {
                  const response = JSON.parse(xhr.responseText);
                  this._uploadComplete(response);
                  resolve(response);
                } catch (error) {
                  const errorMsg = 'Invalid server response';
                  this._uploadError(errorMsg);
                  reject(new Error(errorMsg));
                }
              } else {
                let errorMessage = `Server error: ${xhr.status}`;
                try {
                  const response = JSON.parse(xhr.responseText);
                  if (response.message) {
                    errorMessage = response.message;
                  }
                } catch (e) {
                  // Ignore parse error
                }
                
                this._uploadError(errorMessage);
                reject(new Error(errorMessage));
              }
            });
            
            // Set up error handler
            xhr.addEventListener('error', () => {
              const errorMsg = 'Network error during upload';
              this._uploadError(errorMsg);
              reject(new Error(errorMsg));
            });
            
            // Set up abort handler
            xhr.addEventListener('abort', () => {
              const errorMsg = 'Upload aborted';
              this._uploadError(errorMsg);
              reject(new Error(errorMsg));
            });
            
            // Set headers
            xhr.open('POST', this.config.uploadUrl);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            // Add auth header if available
            if (window.AuthHelper && window.AuthHelper.getToken) {
              const token = window.AuthHelper.getToken();
              if (token) {
                xhr.setRequestHeader('Authorization', `Bearer ${token}`);
              }
            }
            
            // Send the request
            xhr.send(formData);
          })
          .finally(() => {
            this.uploading = false;
            this.xhr = null;
          });
        },
        
        /**
         * Update progress in UI
         * @param {number} progress Progress percentage
         * @private
         */
        _updateProgress(progress) {
          // Update main progress bar
          const progressBar = this.progressContainer.querySelector('.cf-progress-bar-fill');
          const progressPercentage = this.progressContainer.querySelector('.cf-progress-percentage');
          
          if (progressBar) progressBar.style.width = `${progress}%`;
          if (progressPercentage) progressPercentage.textContent = `${progress}%`;
          
          // Update individual file progress
          this.files.forEach(fileObj => {
            const progressElement = fileObj.element?.querySelector('.cf-file-progress');
            if (progressElement) {
              progressElement.textContent = `${progress}%`;
              progressElement.classList.remove('hidden');
            }
          });
        },
        
        /**
         * Handle upload completion
         * @param {Object} response Server response
         * @private
         */
        _uploadComplete(response) {
          // Mark all files as uploaded
          this.files.forEach(fileObj => {
            fileObj.uploaded = true;
            fileObj.progress = 100;
            fileObj.response = response;
            
            // Update UI
            const element = fileObj.element;
            if (element) {
              element.classList.add('bg-green-50');
              
              const progressElement = element.querySelector('.cf-file-progress');
              if (progressElement) {
                progressElement.textContent = 'Uploaded';
                progressElement.classList.remove('text-primary-600');
                progressElement.classList.add('text-green-600');
              }
            }
          });
          
          // Hide upload button
          if (this.uploadButton) {
            this.uploadButton.classList.add('hidden');
          }
          
          // Hide progress bar after delay
          setTimeout(() => {
            this.progressContainer.classList.add('hidden');
          }, 1500);
          
          // Set uploaded flag
          this.uploaded = true;
          
          // Dispatch event
          this._dispatchEvent('cf:upload:complete', { response });
        },
        
        /**
         * Handle upload error
         * @param {string} errorMessage Error message
         * @private
         */
        _uploadError(errorMessage) {
          // Show error message
          this._showError(errorMessage);
          
          // Update progress bar
          const progressBar = this.progressContainer.querySelector('.cf-progress-bar-fill');
          const progressText = this.progressContainer.querySelector('.cf-progress-text');
          
          if (progressBar) progressBar.classList.replace('bg-primary-600', 'bg-red-600');
          if (progressText) progressText.textContent = 'Upload failed';
          
          // Hide progress bar after delay
          setTimeout(() => {
            this.progressContainer.classList.add('hidden');
            
            // Reset progress bar color
            if (progressBar) progressBar.classList.replace('bg-red-600', 'bg-primary-600');
          }, 3000);
          
          // Dispatch event
          this._dispatchEvent('cf:upload:error', { error: errorMessage });
        },
        
        /**
         * Remove file from list
         * @param {string} fileId File ID to remove
         */
        removeFile(fileId) {
          const index = this.files.findIndex(f => f.id === fileId);
          
          if (index === -1) return;
          
          const file = this.files[index];
          
          // Remove from DOM
          if (file.element) {
            file.element.remove();
          }
          
          // Remove from files array
          this.files.splice(index, 1);
          
          // Hide upload button if no files left
          if (this.uploadButton && this.files.length === 0) {
            this.uploadButton.classList.add('hidden');
          }
          
          // Dispatch event
          this._dispatchEvent('cf:file:removed', { file });
        },
        
        /**
         * Abort current upload
         */
        abort() {
          if (this.uploading && this.xhr) {
            this.xhr.abort();
            this.xhr = null;
            this.uploading = false;
          }
        },
        
        /**
         * Clear all files
         */
        clear() {
          // Abort any ongoing upload
          this.abort();
          
          // Clear files array
          this.files = [];
          
          // Clear UI
          this.fileList.innerHTML = '';
          
          // Hide upload button
          if (this.uploadButton) {
            this.uploadButton.classList.add('hidden');
          }
          
          // Reset input
          this.input.value = '';
          
          // Dispatch event
          this._dispatchEvent('cf:files:cleared');
        },
        
        /**
         * Show error message
         * @param {string} message Error message
         * @private
         */
        _showError(message) {
          // Create error element
          const errorElement = document.createElement('div');
          errorElement.className = 'cf-upload-error bg-red-100 border-l-4 border-red-500 text-red-700 p-2 mb-2';
          errorElement.textContent = message;
          
          // Add to container
          this.container.insertBefore(errorElement, this.fileList);
          
          // Remove after delay
          setTimeout(() => {
            errorElement.remove();
          }, 5000);
        },
        
        /**
         * Add CSRF token to form data
         * @param {FormData} formData Form data
         * @private
         */
        _addCsrfToken(formData) {
          // Try to get token from security module
          let csrfToken = null;
          
          if (window.CarFuseSecurity && window.CarFuseSecurity.getCsrfToken) {
            csrfToken = window.CarFuseSecurity.getCsrfToken();
          } else if (window.AuthHelper && window.AuthHelper.getCsrfToken) {
            csrfToken = window.AuthHelper.getCsrfToken();
          } else {
            // Fallback to meta tag
            csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          }
          
          if (csrfToken) {
            formData.append(this.config.csrfTokenName, csrfToken);
          }
        },
        
        /**
         * Compress image file
         * @param {Object} fileObj File object
         * @param {HTMLImageElement} img Image element
         * @private
         */
        _compressImage(fileObj, img) {
          const canvas = document.createElement('canvas');
          canvas.width = img.width;
          canvas.height = img.height;
          
          // Draw image on canvas
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0);
          
          // Convert to blob with compression
          canvas.toBlob(
            blob => {
              if (blob) {
                // Create new file
                const compressedFile = new File([blob], fileObj.file.name, {
                  type: fileObj.file.type,
                  lastModified: fileObj.file.lastModified
                });
                
                // Update file object
                fileObj.originalFile = fileObj.file;
                fileObj.file = compressedFile;
                fileObj.size = compressedFile.size;
                
                // Update file size in UI
                const sizeElement = fileObj.element?.querySelector('.cf-file-size');
                if (sizeElement) {
                  sizeElement.textContent = this._formatSize(compressedFile.size);
                }
              }
            },
            fileObj.file.type,
            this.config.compressionQuality
          );
        },
        
        /**
         * Get file type icon HTML
         * @param {string} fileType MIME type
         * @returns {string} Icon HTML
         * @private
         */
        _getFileTypeIcon(fileType) {
          if (fileType.startsWith('image/')) {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>`;
          } else if (fileType.startsWith('video/')) {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>`;
          } else if (fileType.startsWith('application/pdf')) {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-700"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>`;
          } else if (fileType.includes('spreadsheet') || fileType.includes('excel')) {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polygon points="8 9 10 9 10 9"></polygon></svg>`;
          } else {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>`;
          }
        },
        
        /**
         * Get file extension from filename
         * @param {string} filename Filename
         * @returns {string} File extension
         * @private
         */
        _getFileExtension(filename) {
          return filename.split('.').pop().toLowerCase();
        },
        
        /**
         * Check if file type is allowed
         * @param {string} fileType MIME type or extension
         * @returns {boolean} Whether file type is allowed
         * @private
         */
        _isAllowedFileType(fileType) {
          if (!this.config.allowedTypes || this.config.allowedTypes.length === 0) {
            return true;
          }
          
          return this.config.allowedTypes.some(allowedType => {
            // Check wildcards (e.g., "image/*")
            if (allowedType.endsWith('/*')) {
              const prefix = allowedType.replace('/*', '/');
              return fileType.startsWith(prefix);
            }
            
            return allowedType === fileType;
          });
        },
        
        /**
         * Format file size for display
         * @param {number} bytes Size in bytes
         * @returns {string} Formatted size
         * @private
         */
        _formatSize(bytes) {
          if (bytes === 0) return '0 B';
          
          const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
          const i = Math.floor(Math.log(bytes) / Math.log(1024));
          
          return (bytes / Math.pow(1024, i)).toFixed(i > 0 ? 2 : 0) + ' ' + sizes[i];
        },
        
        /**
         * Dispatch custom event
         * @param {string} eventName Event name
         * @param {Object} detail Event detail
         * @private
         */
        _dispatchEvent(eventName, detail = {}) {
          const event = new CustomEvent(eventName, {
            bubbles: true,
            detail: { ...detail, uploader: this }
          });
          
          this.input.dispatchEvent(event);
        }
      };
      
      return uploader.init();
    },
    
    /**
     * Initialize file upload functionality on existing inputs
     * @param {string} selector CSS selector for file inputs to enhance
     * @param {Object} options Upload options
     * @returns {Array} Array of created uploaders
     */
    enhance(selector = 'input[type="file"][data-cf-upload]', options = {}) {
      const inputs = document.querySelectorAll(selector);
      const uploaders = [];
      
      inputs.forEach(input => {
        // Parse options from data attributes
        const inputOptions = this._parseDataOptions(input);
        
        // Create uploader
        const uploader = this.createUploader(input, { ...options, ...inputOptions });
        uploaders.push(uploader);
      });
      
      return uploaders;
    },
    
    /**
     * Parse options from data attributes
     * @param {HTMLElement} element Element with data attributes
     * @returns {Object} Parsed options
     * @private
     */
    _parseDataOptions(element) {
      const options = {};
      
      // Parse specific upload options
      try {
        if (element.dataset.cfUpload) {
          const jsonOptions = element.dataset.cfUpload.trim();
          if (jsonOptions && jsonOptions !== 'true') {
            Object.assign(options, JSON.parse(jsonOptions));
          }
        }
      } catch (e) {
        console.error('Error parsing upload options:', e);
      }
      
      // Look for individual options
      if (element.dataset.cfMultiple !== undefined) {
        options.multipleFiles = element.dataset.cfMultiple === 'true';
      }
      
      if (element.dataset.cfMaxSize) {
        options.maxFileSize = parseInt(element.dataset.cfMaxSize) * 1024 * 1024;
      }
      
      if (element.dataset.cfAutoUpload !== undefined) {
        options.autoUpload = element.dataset.cfAutoUpload === 'true';
      }
      
      if (element.dataset.cfAllowedTypes) {
        options.allowedTypes = element.dataset.cfAllowedTypes.split(',').map(t => t.trim());
      }
      
      if (element.dataset.cfUploadUrl) {
        options.uploadUrl = element.dataset.cfUploadUrl;
      }
      
      return options;
    }
  };
  
  // Register with CarFuse
  if (!CarFuse.forms) CarFuse.forms = {};
  CarFuse.forms.uploads = uploads;
  
  // Export to window in case CarFuse is not available
  if (!window.CarFuse) {
    window.CarFuseForms = window.CarFuseForms || {};
    window.CarFuseForms.uploads = uploads;
  }
})();
