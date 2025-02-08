import os

# Raw CSS text provided
css_text = """
/* main.css - Applied Across All Pages */
body {
    font-family: 'Poppins', sans-serif;
    color: #EEE;
    background: #121212;
}


.btn-primary {
    background: linear-gradient(45deg, #FFD700, #FF8C00);
    color: #000;
    padding: 10px 15px;
    border-radius: 6px;
    transition: 0.3s;
    font-weight: bold;
}
.btn-primary:hover {
    filter: brightness(1.2);
}

/* dashboard.css - Dark, Sleek Admin & User Dashboard Styles */
.dashboard-container {
    background-color: #121212;
    color: #EEE;
    padding: 20px;
}
.dashboard-widget {
    background: #1E1E1E;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0px 0px 8px rgba(255, 255, 255, 0.1);
    transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
}
.dashboard-widget:hover {
    transform: scale(1.05);
    box-shadow: 0px 0px 12px rgba(255, 255, 255, 0.15);
}
.sidebar {
    background: #181818;
    width: 250px;
}
.sidebar a.active {
    color: #FFD700;
}

/* bookings.css - Booking System Styling */
.booking-form input {
    background: #1E1E1E;
    border: 1px solid #333;
    color: #FFF;
    padding: 10px;
    width: 100%;
}
.booking-form button {
    @extend .btn-primary;
}

/* payments.css - Payment Processing UI */
.payment-container {
    background: #1E1E1E;
    padding: 20px;
    border-radius: 6px;
}
.payment-form input {
    background: #181818;
    color: #FFF;
    width: 100%;
}
.payment-form button {
    @extend .btn-primary;
}

/* documents.css - Document Management UI */
.document-upload {
    border: 2px dashed #FFD700;
    padding: 20px;
    text-align: center;
}
.document-preview {
    background: #2A2A2A;
    padding: 10px;
}

/* notifications.css - Real-Time Notifications */
.notifications-panel {
    position: absolute;
    right: 20px;
    top: 50px;
    background: #1E1E1E;
    color: #FFF;
    padding: 15px;
    border-radius: 5px;
}
.notification.unread {
    background: #1E1E1E;
    font-weight: bold;
}
.notification.read {
    background: #252525;
    opacity: 0.85;
}

/* profile.css - User Profile Page */
.profile-card {
    background: rgba(30,30,30,0.8);
    border-radius: 10px;
    padding: 20px;
}
.avatar-upload img {
    border-radius: 50%;
    box-shadow: 0px 0px 10px rgba(255, 215, 0, 0.5);
}

/* toasts.css - Toast Notifications */
.toast-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    max-width: 300px;
}
.toast {
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 10px;
    transition: opacity 0.3s ease-in-out;
    opacity: 0;
    transform: translateY(10px);
    animation: fadeInUp 0.3s ease-in-out forwards;
}
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.toast-success {
    background: #2ECC71;
    color: #FFF;
}
.toast-error {
    background: #E74C3C;
    color: #FFF;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 10px;
    }
    .dashboard-widget {
        width: 100%;
        margin-bottom: 15px;
    }
    .booking-form input,
    .payment-form input {
        width: 100%;
    }
    .toast-container {
        right: 10px;
        bottom: 10px;
        max-width: 250px;
    }
}

"""

# Function to parse CSS text and write to corresponding files
def parse_and_create_files(css_text):
    # Split the input text into individual CSS sections
    sections = css_text.strip().split("/*")
    
    # Iterate through each section to find the section title and content
    for section in sections:
        if section.strip():  # Skip empty sections
            # Extract the section title (filename) and content
            title, content = section.split("*/", 1)
            title = title.strip().replace("-", "_")  # Sanitize the title for filenames
            filename = f"{title}.css"
            
            # Write the content to the respective file
            with open(filename, 'w') as file:
                file.write(f"/* {title} */\n{content.strip()}\n")
            print(f"File '{filename}' created successfully.")

# Run the function to parse and create the CSS files
parse_and_create_files(css_text)
