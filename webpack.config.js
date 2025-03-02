/**
 * External dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

/**
 * WordPress dependencies
 */
const { getWebpackEntryPoints } = require('@wordpress/scripts/utils/config');

module.exports = {
    ...defaultConfig,
    entry: {
        index: path.resolve(process.cwd(), 'src', 'index.js'),
        frontend: path.resolve(process.cwd(), 'src', 'frontend.js'),
    },
    externals: {
        ...defaultConfig.externals,
        '@wordpress/components': 'wp.components',
        '@wordpress/element': 'wp.element',
        '@wordpress/i18n': 'wp.i18n'
    }
}; 