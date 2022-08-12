import React, { useEffect, useState } from 'react';

const VIDEO_FORMAT = {
    0: 'Raw Video',
    2: 'Basic/Small',
    4: 'SD/Small',
    5: 'SD/Large',
    7: 'HD/1080'
}

const KALTURA_SERVICE_URL     = ubc_h5p_kaltura_integration_admin.kaltura_service_url;
const KALTURA_PARTNER_ID      = ubc_h5p_kaltura_integration_admin.kaltura_partner_id;
const KALTURA_STREAMING_FORMAT = 'download';
const KALTURA_PROTOCOL        = 'https';

const downArrowSVG = () => {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
            <path d="M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"/>
        </svg>
    );
}

export default props => {
    const [kalturaID, setKalturaID] = useState('');
    const [kalturaFormat, setKalturaFormat] = useState(7);
    const [isVisible, setIsVisible] = useState(false);
    const [message, setMessage] = useState('');
    const [isValid, setIsValid] = useState(null);
    const [isInputDisabled, setIsInputdisabled] = useState(false);

    const inputElement = props.rootParent.querySelector('.h5p-file-url');

    useEffect(() => {
        const insertButton = props.rootParent.querySelector('.h5p-insert');
        const cancelButton = props.rootParent.querySelector('.h5p-cancel');

        insertButton.addEventListener('click', () => {
            resetStates();
        })

        cancelButton.addEventListener('click', () => {
            resetStates();
        })
    }, [])

    const resetStates = () => {
        setKalturaID('');
        setKalturaFormat(7);
        setIsVisible(false);
        setMessage('');
        setIsValid(false);
        setIsInputdisabled(false);
    }

    const generateKalturaVideoURL = async () => {
        if( ! kalturaID ) {
            setIsValid(false);
            setMessage('Kaltura video ID is required.');
            return;
        }

        const videoUrl = `${KALTURA_SERVICE_URL}/p/${KALTURA_PARTNER_ID}/sp/0/playManifest/entryId/${kalturaID}/format/${KALTURA_STREAMING_FORMAT}/protocol/${KALTURA_PROTOCOL}/flavorParamIds/${kalturaFormat}/`;

        let formData = new FormData();

        formData.append( 'action', 'ubc_h5p_kaltura_verify_source' );
        formData.append( 'nonce', ubc_h5p_kaltura_integration_admin.security_nonce );
        formData.append( 'video_url', videoUrl );

        setActionsDisabled();

        let response = await fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        response = await response.json();

        setActionsEnabled();
        setIsValid(response.valid);
        setMessage(response.message);

        inputElement.value = response.valid ? videoUrl : '';
    }

    const setActionsDisabled = () => {
        const insertButton = props.rootParent.querySelector('.h5p-insert');
        const cancelButton = props.rootParent.querySelector('.h5p-cancel');

        setIsInputdisabled(true);
        insertButton.disabled = true;
        cancelButton.disabled = true;
    }

    const setActionsEnabled = () => {
        const insertButton = props.rootParent.querySelector('.h5p-insert');
        const cancelButton = props.rootParent.querySelector('.h5p-cancel');

        setIsInputdisabled(false);
        insertButton.disabled = false;
        cancelButton.disabled = false;
    }

    const renderKalturaFields = () => {
        return (
            <div
                style={{
                    padding: '0 20px 20px 20px'
                }}
                className='field'
            >
                <h3>Video ID</h3>
                <input 
                    type="text" 
                    placeholder='Enter UBC kaltura video ID. Eg, 0_mxcjbk76' 
                    className="h5peditor-text" 
                    value={kalturaID}
                    onChange={e => {
                        setKalturaID( e.target.value );
                    }}
                    disabled={ isInputDisabled }
                ></input>
                <p className='h5peditor-field-description'>Please make sure the Kaltura video ID you entered is correct and click 'Generate' button</p>

                <h3>Video Format</h3>
                <select
                    onChange={e => {
                        setKalturaFormat( e.target.value );
                    }}
                    value={kalturaFormat}
                    disabled={ isInputDisabled }
                    className='h5peditor-select'
                >
                    { Object.keys(VIDEO_FORMAT).map( (key, index ) => {
                        return <option key={ `video-format-option-${index}` } value={key}>{ VIDEO_FORMAT[key] }</option>
                    } ) }
                </select>
                { '' !== message ? <div className={`${ isValid ? 'valid' : 'invalid' } h5p-notice`}> 
                    <p><strong>{ message }</strong></p>
                </div> : null }

                { isInputDisabled ? <div className="loadingio-spinner-eclipse-1tbcqwrifq2">
                    <div className="ldio-zlghrr0663d">
                        <div></div>
                    </div>
                </div> : null }

                { ! isInputDisabled ? <button
                    className='h5peditor-button h5peditor-button-textual importance-high'
                    style={{
                        marginTop: '1.5rem'
                    }}
                    onClick={() => {
                        generateKalturaVideoURL();
                    }}
                >Generate</button> : null }
            </div>
        );
    }

    return (
        <>
            <div className='h5p-divider'></div>
            <div className='field kaltura-integration'>
                <div
                    className='kaltura-integration-accordion'
                    onClick={() => {
                        setIsVisible(!isVisible);
                    }}
                >
                    <div>
                        <h3
                            style={{
                                marginBottom: 0
                            }}
                        >Use UBC Kaltura Video</h3>
                        <div className='h5peditor-field-description'>See how to <a href={`${ ubc_h5p_kaltura_integration_admin.kaltura_instruction_url }`} target="_blank">find the ID for you videos</a> you have uploaded to Kaltura</div>
                    </div>
                    { downArrowSVG() }
                </div>
                { isVisible ? renderKalturaFields() : null }
            </div>
        </>
    );
};