import React from 'react';
import ReactDOM from 'react-dom';
import App from '../assets/src/js/h5p-new';
import '../assets/src/css/h5p-new.scss';

window.H5PEditorOnIframeLoaded( () => {
	renderKalturaStyles();

	/*
	* Render Kaltura video dom to replace existing video/audio upload sectin on page load.
	*/
	const fieldsOnLoad = document.querySelector('.h5p-editor-iframe').contentDocument.querySelectorAll('.h5p-add-dialog-table');
	fieldsOnLoad.forEach(field => {
		renderKalturaDom(field);
	});
	
	/*
	* More complicated content type that use video/audio as a widget or subtype.
	*/

	const targetNode = document.querySelector('.h5p-editor-iframe').contentDocument.querySelector('.h5peditor-form');
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

})

function renderKalturaDom( relativeDom = null ) {
	const dialogTable = relativeDom ? relativeDom : document.querySelector('.h5p-editor-iframe').contentDocument.querySelector('.h5p-add-dialog-table');

	// Remove upload videos and vertical line
	dialogTable.removeChild(dialogTable.firstElementChild);
	dialogTable.removeChild(dialogTable.firstElementChild);

	// Add new div
	dialogTable
	.querySelector('.h5p-dialog-box')
	.insertAdjacentHTML('beforeend', '<div class=\"h5p-kultura-integration\"></div>');

	// Render application
	ReactDOM.render(
		<App
			rootParent={dialogTable.closest('.h5p-add-dialog')}
		/>,
		// eslint-disable-next-line no-undef
		dialogTable.querySelector('.h5p-kultura-integration')
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