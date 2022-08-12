# H5P Addon - Kaltura Integration
**this add-on adds extra functionality on top of H5P WordPress plugin. Please make sure the H5P plugin is installed and activated.*

this add-on provides UI for users to pull video from UBC Kaltura and embed into h5p contents.

### Local Environment
Install node packages
`npm install`

Start building JS and CSS for development
`npm start`

Build JS and CSS for production
`npm build`

Install phpcs with WordPress coding standard
`composer install`

## Change Log

### 1.0.7
- Use wp_remote_head function when validating the media URL to prevent large video times out the HTTP request.

### 1.0.6
- Improve the way to determine if there's a video upload field need to be replace on page load. This should make the script serve a more general case and work for all content types theoratically.