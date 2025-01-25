import { ref } from 'vue'

// Cache for storing API responses
const cache = new Map();
const loadingStates = new Map()

export function useLoading(key) {
  if (!loadingStates.has(key)) {
    loadingStates.set(key, ref(false))
  }
  return loadingStates.get(key)
}

export async function fetchData(endpoint, config = {}) {
  const cacheKey = `${endpoint}-${JSON.stringify(config)}`
  const loading = useLoading(cacheKey)
  
  // Check cache first
  if (!config.noCache && cache.has(cacheKey)) {
    return cache.get(cacheKey);
  }

  loading.value = true
  try {
    const response = await fetch(endpoint, {
      headers: {
        'Content-Type': 'application/json',
        ...config.headers
      },
      ...config
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    
    // Cache the response if caching is not disabled
    if (!config.noCache) {
      cache.set(cacheKey, data);
    }

    return data;
  } catch (error) {
    console.error('Fetch error:', error);
    throw error;
  } finally {
    loading.value = false
  }
}

// Clear cache for specific endpoint or all cache
export function clearCache(pattern = null) {
  if (pattern) {
    [...cache.keys()]
      .filter(key => key.includes(pattern))
      .forEach(key => cache.delete(key));
  } else {
    cache.clear();
  }
}

// Error handling utility
export function handleError(error, component) {
  console.error(`Error in ${component}:`, error);
  // You could emit events, show notifications, etc.
  return {
    error: true,
    message: error.message || 'An unexpected error occurred'
  };
}
