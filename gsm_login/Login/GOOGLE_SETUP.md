# Google OAuth Setup Instructions

## Step 1: Create Google Cloud Project
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the Google+ API

## Step 2: Create OAuth 2.0 Credentials
1. Go to "Credentials" in the left sidebar
2. Click "Create Credentials" → "OAuth 2.0 Client IDs"
3. Choose "Web application"
4. Add authorized origins:
   - `http://localhost`
   - `http://localhost:80`
5. Add authorized redirect URIs:
   - `http://localhost/gsm_login/Login/`
   - `http://localhost/gsm_login/Login/index.html`

## Step 3: Update Configuration
1. Copy your Client ID from Google Cloud Console
2. Replace `YOUR_GOOGLE_CLIENT_ID` in these files:
   - `index.html` (2 places)
   - `google_auth.php` (1 place)

## Step 4: Test
1. Refresh the login page
2. Click "Continue with Google"
3. Sign in with your Google account
4. Should automatically create account and login

## Features
- Auto-creates user account on first Google login
- Assigns "commuter" role by default
- Uses Google profile info (name, email)
- Seamless login experience