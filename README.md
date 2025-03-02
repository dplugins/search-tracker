https://github.com/user-attachments/assets/17e08b46-31b3-4a8d-9dbd-de3ce2f1cbca

# Search Query Tracker

A WordPress plugin that tracks search queries and user clicks on search results.

## Features

- Tracks search queries performed on your WordPress site
- Tracks which search results users click on
- Provides a beautiful admin dashboard to view search data
- Allows filtering and sorting of search queries
- Shows detailed click data for each search query

<img width="2560" alt="Image" src="https://github.com/user-attachments/assets/c65a5fe3-d6e8-4754-b621-92731a5269e1" />

## Installation

1. Upload the `search-tracker` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the dashboard via the 'Search Tracker' menu in the admin sidebar

## Development

This plugin uses React for the admin interface and modern JavaScript for the frontend tracking.

### Prerequisites

- Node.js and npm

### Setup

1. Clone the repository
2. Run `npm install` to install dependencies
3. Run `npm run start` to start the development server
4. Run `npm run build` to build the production assets

### Structure

- `src/` - React components and JavaScript source files
- `app/` - PHP files for the plugin backend
- `build/` - Compiled JavaScript files (generated)

## Usage

### Tracking Search Clicks

To track clicks on search results, add the class `.search-result-item` to your search result items in your theme's search template.

Example:

```php
<div class="search-result-item">
    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
</div>
```

## License

This project is licensed under the ISC License. 