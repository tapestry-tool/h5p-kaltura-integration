import React from 'react';
import ReactDOM from 'react-dom';
import App from '../assets/src/js/h5p-new';
import '../assets/src/css/h5p-new.scss';

window.onload = () => {
	renderKalturaStyles();

	/*
	* Render Kaltura video dom alongside the existing video/audio upload section on page load.
	*/
	const fieldsOnLoad = document.querySelector('.h5p-editor-iframe').contentDocument.querySelectorAll('.h5p-add-dialog-table');
	fieldsOnLoad.forEach(field => {
		renderKalturaDom(field);
	});
	
	/*
	* More complicated content type that use video/audio as a widget or subtype.
	*/
	const targetNode = document.querySelector('.h5p-editor-iframe').contentDocument;
	const config = { attributes: false, childList: true, subtree: true };

	// Callback function to execute when mutations are observed
	const callback = function(mutationsList, observer) {
		// Use traditional 'for loops' for IE 11
		for(const mutation of mutationsList) {
			if( mutation.addedNodes[0]
			&& jQuery(mutation.addedNodes[0]).find('.h5p-add-dialog-table')
			&& jQuery(mutation.addedNodes[0]).find('.h5p-add-dialog-table').children()
			&& jQuery(mutation.addedNodes[0]).find('.h5p-add-dialog-table').children().length === 3
			) {
				renderKalturaDom( mutation.addedNodes[0].querySelector('.h5p-add-dialog-table') );
				break;
			}
		}
	};

	// Create an observer instance linked to the callback function
	const observer = new MutationObserver(callback);

	// Start observing the target node for configured mutations
	observer.observe(targetNode, config);

};

function renderKalturaDom( relativeDom = null ) {
	const dialogTable = relativeDom ? relativeDom : document.querySelector('.h5p-editor-iframe').contentDocument.querySelector('.h5p-add-dialog-table');

	// Add new div after box for video source URL
	dialogTable
	.querySelectorAll('.h5p-dialog-box')[1]
	.insertAdjacentHTML('afterend', '<div class=\"h5p-kaltura-integration\"></div>');

	// Render application
	ReactDOM.render(
		<App
			rootParent={dialogTable.closest('.h5p-add-dialog')}
		/>,
		// eslint-disable-next-line no-undef
		dialogTable.querySelector('.h5p-kaltura-integration')
	);
}

function renderKalturaStyles() {
	// Append CSS file
	var head = document.querySelector('.h5p-editor-iframe').contentDocument.getElementsByTagName('HEAD')[0]; 
	var link = document.querySelector('.h5p-editor-iframe').contentDocument.createElement('link');

	link.rel = 'stylesheet'; 
	link.type = 'text/css';
	link.href = `${ubc_h5p_kaltura_integration_admin.plugin_url}assets/dist/css/app.css?ver=${ubc_h5p_kaltura_integration_admin.iframe_css_file_version}`; 
	head.appendChild(link);
}