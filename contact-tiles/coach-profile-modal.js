(function($) {
  'use strict';

  // Verify jQuery is available
  if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
    console.error('Coach Profile Modal: jQuery is not available');
    return;
  }

  // Wait for both jQuery and DOM to be ready
  $(document).ready(function() {
    console.log('Coach Profile Modal: Script loaded and ready');
    
    const escape = window.SHAREDFUNCTIONS ? window.SHAREDFUNCTIONS.escapeHTML : function(str) {
      if (typeof str === 'undefined' || str === null) return '';
      if (typeof str !== 'string') return str;
      const div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    };

    // Get REST API URL and nonce from localized script
    const restUrl = (window.coachProfileModalSettings && window.coachProfileModalSettings.rest_url) 
      ? window.coachProfileModalSettings.rest_url 
      : (window.wpApiShare && window.wpApiShare.root ? window.wpApiShare.root : '');
    const nonce = (window.coachProfileModalSettings && window.coachProfileModalSettings.nonce)
      ? window.coachProfileModalSettings.nonce
      : (window.wpApiShare && window.wpApiShare.nonce ? window.wpApiShare.nonce : '');

    // Accepted file types for images
    const acceptedFileTypes = ['image/png', 'image/gif', 'image/jpeg', 'image/jpg'];

    // Translations
    const translations = (window.coachProfileModalSettings && window.coachProfileModalSettings.translations)
      ? window.coachProfileModalSettings.translations
      : {
          title: 'File Upload',
          choose_file: 'Choose a file',
          or_drag_it: 'or drag it here',
          success: 'Successfully Uploaded!',
          error: 'Error!',
          error_msg: 'Unable to upload, please try again',
          but_upload: 'Upload',
          but_delete: 'Delete Existing File',
          but_replace: 'Replace Existing Image',
          delete_msg: 'Are you sure you wish to delete existing file?',
          delete_success_msg: 'Successfully Deleted!',
          delete_error_msg: 'Delete failed, please try again',
          but_close: 'Close'
        };

    // Handle click on coach profile upload button
    // Use both delegated and direct event handlers for maximum compatibility
    $(document).on('click', '.coach-profile-upload-button', function (e) {
      e.preventDefault();
      e.stopPropagation();
      
      const element = $(this);
      const userId = element.data('coach-upload-user-id') || element.attr('data-coach-upload-user-id');
      const metaKey = element.data('coach-upload-meta-key') || element.attr('data-coach-upload-meta-key') || 'coach_profile_photo';
      const keyPrefix = element.data('coach-upload-key-prefix') || element.attr('data-coach-upload-key-prefix') || 'users';
      const deleteEnabled = element.data('coach-upload-delete-enabled') !== undefined || element.attr('data-coach-upload-delete-enabled') !== undefined;

      if (!userId) {
        console.error('Coach profile upload: User ID is missing', element);
        alert('Error: User ID is missing. Please refresh the page and try again.');
        return;
      }

      console.log('Coach profile upload button clicked', { userId, metaKey, keyPrefix, deleteEnabled });
      display_coach_upload_modal(userId, metaKey, keyPrefix, deleteEnabled);
    });

  /**
   * Display coach profile upload modal
   */
  function display_coach_upload_modal(
    user_id,
    meta_key,
    key_prefix,
    delete_enabled = false,
  ) {
    const modal_html = `
      <div class="reveal medium" id="coach_storage_upload_modal" data-reveal data-reset-on-close>
        <style>
          #coach_storage_upload_modal
          {
              text-align: center;
          }

          .box
          {
              font-size: 1.25rem; /* 20 */
              background-color: #c8dadf;
              position: relative;
              padding: 100px 20px;
          }
          .box.has-advanced-upload
          {
              outline: 2px dashed #92b0b3;
              outline-offset: -10px;

              -webkit-transition: outline-offset .15s ease-in-out, background-color .15s linear;
              transition: outline-offset .15s ease-in-out, background-color .15s linear;
          }
          .box.is-dragover
          {
              outline-offset: -20px;
              outline-color: #c8dadf;
              background-color: #fff;
          }
          .box__dragndrop,
          .box__icon
          {
              display: none;
          }
          .box.has-advanced-upload .box__dragndrop
          {
              display: inline;
          }
          .box.has-advanced-upload .box__icon
          {
              width: 100%;
              height: 80px;
              fill: #92b0b3;
              display: block;
              margin-bottom: 40px;
          }

          .box.is-uploading .box__input,
          .box.is-success .box__input,
          .box.is-error .box__input
          {
              visibility: hidden;
          }

          .box__uploading,
          .box__success,
          .box__error
          {
              display: none;
          }
          .box.is-uploading .box__uploading,
          .box.is-success .box__success,
          .box.is-error .box__error
          {
              display: block;
              position: absolute;
              top: 50%;
              right: 0;
              left: 0;

              -webkit-transform: translateY( -50% );
              transform: translateY( -50% );
          }
          .box__uploading
          {
              font-style: italic;
          }
          .box__success
          {
              -webkit-animation: appear-from-inside .25s ease-in-out;
              animation: appear-from-inside .25s ease-in-out;
          }
          @-webkit-keyframes appear-from-inside
          {
              from { -webkit-transform: translateY( -50% ) scale( 0 ); }
              75% { -webkit-transform: translateY( -50% ) scale( 1.1 ); }
              to { -webkit-transform: translateY( -50% ) scale( 1 ); }
          }
          @keyframes appear-from-inside
          {
              from { transform: translateY( -50% ) scale( 0 ); }
              75% { transform: translateY( -50% ) scale( 1.1 ); }
              to { transform: translateY( -50% ) scale( 1 ); }
          }

          .box__restart
          {
              font-weight: 700;
          }
          .box__restart:focus,
          .box__restart:hover
          {
              color: #39bfd3;
          }

          .js .box__file
          {
              width: 0.1px;
              height: 0.1px;
              opacity: 0;
              overflow: hidden;
              position: absolute;
              z-index: -1;
          }
          .js .box__file + label
          {
              max-width: 80%;
              text-overflow: ellipsis;
              white-space: nowrap;
              cursor: pointer;
              display: inline-block;
              overflow: hidden;
          }
          .js .box__file + label:hover strong,
          .box__file:focus + label strong,
          .box__file.has-focus + label strong
          {
              color: #39bfd3;
          }
          .js .box__file:focus + label,
          .js .box__file.has-focus + label
          {
              outline: 1px dotted #000;
              outline: -webkit-focus-ring-color auto 5px;
          }
          .js .box__file + label *
          {
              /* pointer-events: none; */ /* in case of FastClick lib use */
          }

          .no-js .box__file + label
          {
              display: none;
          }

          .box__button_upload
          {
              display: none;
          }
        </style>
        <h3>${escape(translations.title)}</h3>

        <form class="box" method="POST" action="${restUrl + 'zume_coaching/v1/users/' + user_id + '/storage_upload'}" enctype="multipart/form-data">
            <div class="box__input">
                <svg class="box__icon" xmlns="http://www.w3.org/2000/svg" width="50" height="43" viewBox="0 0 50 43">
                  <path d="M48.4 26.5c-.9 0-1.7.7-1.7 1.7v11.6h-43.3v-11.6c0-.9-.7-1.7-1.7-1.7s-1.7.7-1.7 1.7v13.2c0 .9.7 1.7 1.7 1.7h46.7c.9 0 1.7-.7 1.7-1.7v-13.2c0-1-.7-1.7-1.7-1.7zm-24.5 6.1c.3.3.8.5 1.2.5.4 0 .9-.2 1.2-.5l10-11.6c.7-.7.7-1.7 0-2.4s-1.7-.7-2.4 0l-7.1 8.3v-25.3c0-.9-.7-1.7-1.7-1.7s-1.7.7-1.7 1.7v25.3l-7.1-8.3c-.7-.7-1.7-.7-2.4 0s-.7 1.7 0 2.4l10 11.6z"/>
                </svg>
                <input class="box__file" type="file" name="storage_upload_files[]" id="coach_storage_upload_file" data-multiple-caption="{count} files selected" accept="${acceptedFileTypes.join(',')}" />
                <label for="coach_storage_upload_file"><strong>${escape(translations.choose_file)}</strong><span class="box__dragndrop"> ${escape(translations.or_drag_it)}</span>.</label>
            </div>
            <div class="box__uploading"><span class="loading-spinner active"></span></div>
            <div class="box__success">${escape(translations.success)}</div>
            <div class="box__error">${escape(translations.error)} <span></span>.</div>

            <br>
            <a class="button box__button_upload">${escape(translations.but_upload)}</a>
        </form>

        <br>
        <button class="button box__button_delete" style="display: ${delete_enabled ? `initial` : `none`};">${escape(translations.but_delete)}</button>

        <button class="close-button coach-modal-close" data-close aria-label="${escape(translations.but_close)}" type="button">
            <span aria-hidden="true">&times;</span>
        </button>
      </div>`;

    // Ensure to remove previous stale modal html, before appending generated code.
    $(document.body).find('[id=coach_storage_upload_modal]').remove();
    $(document.body).append(modal_html);

    // Activate upload widgets.
    activate_coach_upload_modal_widgets(
      user_id,
      meta_key,
      key_prefix,
    );

    // Reload reveal foundation object, in order to detect recently added upload modal element.
    // Wait a bit for Foundation to be available
    setTimeout(function() {
      if (typeof $(document).foundation === 'function') {
        $(document).foundation();
      }
      
      // Try to open modal - use Foundation if available, otherwise use basic show
      const $modal = $('#coach_storage_upload_modal');
      if ($modal.length) {
        // Add overlay
        if ($('#coach-storage-modal-overlay').length === 0) {
          $('body').append('<div id="coach-storage-modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.75); z-index: 999998; display: none;"></div>');
        }
        const $overlay = $('#coach-storage-modal-overlay');
        
        if (typeof $(document).foundation === 'function' && typeof $modal.foundation === 'function') {
          $modal.foundation('open');
        } else {
          // Fallback if Foundation not available
          $overlay.show();
          $modal.css({
            'display': 'block',
            'position': 'fixed',
            'top': '50%',
            'left': '50%',
            'transform': 'translate(-50%, -50%)',
            'z-index': '999999',
            'background': 'white',
            'padding': '2rem',
            'border-radius': '4px',
            'max-width': '90%',
            'max-height': '90%',
            'overflow': 'auto'
          });
          $modal.addClass('is-open');
          
          // Close on overlay click
          $overlay.off('click').on('click', function() {
            $modal.hide();
            $overlay.hide();
          });
          
          // Close button handler
          $modal.find('.coach-modal-close').off('click').on('click', function() {
            $modal.hide();
            $overlay.hide();
          });
        }
      } else {
        console.error('Coach profile modal: Modal element not found after creation');
      }
    }, 100);
  }

  function activate_coach_upload_modal_widgets(
    user_id,
    meta_key,
    key_prefix,
  ) {
    // Determine feature detection for drag & drop upload capabilities.
    const is_advanced_upload = (function () {
      const div = document.createElement('div');
      return (
        ('draggable' in div || ('ondragstart' in div && 'ondrop' in div)) &&
        'FormData' in window &&
        'FileReader' in window
      );
    })();

    // Determine if selected file type is accepted.
    const is_file_type_accepted = function (
      file_type,
      accepted_file_types = [],
    ) {
      const idx = accepted_file_types.findIndex((accepted_type) => {
        if (
          accepted_type.endsWith('/*') &&
          file_type.startsWith(
            accepted_type.substring(0, accepted_type.indexOf('/*')),
          )
        ) {
          return true;
        } else if (accepted_type === file_type) {
          return true;
        }
        return false;
      }, file_type);

      return idx > -1;
    };

    // Activate upload form.
    $('.box').each(function () {
      let $form = $(this),
        $input = $form.find('input[type="file"]'),
        $label = $form.find('label'),
        $error_msg = $form.find('.box__error span'),
        $upload_button = $form.find('.box__button_upload'),
        $delete_button = $form.parent().find('.box__button_delete'),
        $restart = $form.find('.box__restart'),
        dropped_files = false,
        show_files = function (files) {
          $label.text(
            files.length > 1
              ? ($input.attr('data-multiple-caption') || '').replace(
                  '{count}',
                  files.length,
                )
              : files[0].name,
          );

          // Display upload button.
          $upload_button.fadeIn('slow');
        };

      // Display selected files.
      $input.on('change', function (e) {
        show_files(e.target.files);
      });

      // Drag & Drop files, if the feature is available.
      if (is_advanced_upload) {
        $form
          .addClass('has-advanced-upload') // letting the CSS part to know drag&drop is supported by the browser
          .on(
            'drag dragstart dragend dragover dragenter dragleave drop',
            function (e) {
              e.preventDefault();
              e.stopPropagation();
            },
          )
          .on('dragover dragenter', function () {
            if (
              !$form.hasClass('is-uploading') &&
              !$form.hasClass('is-success') &&
              !$form.hasClass('is-error')
            ) {
              $form.addClass('is-dragover');
            }
          })
          .on('dragleave dragend drop', function () {
            if (
              !$form.hasClass('is-uploading') &&
              !$form.hasClass('is-success') &&
              !$form.hasClass('is-error')
            ) {
              $form.removeClass('is-dragover');
            }
          })
          .on('drop', function (e) {
            if (
              !$form.hasClass('is-uploading') &&
              !$form.hasClass('is-success') &&
              !$form.hasClass('is-error')
            ) {
              // Enforce only single file uploads.
              const initial_drop = e.originalEvent.dataTransfer.files; // the files that were dropped
              if (initial_drop) {
                // Only proceed with first dropped file, if multiple selections detected.
                if (initial_drop?.length > 1) {
                  dropped_files = [];
                  dropped_files.push(initial_drop[0]);
                } else {
                  dropped_files = initial_drop;
                }

                // Final sanity check to ensure dropped file's type is accepted!
                if (
                  is_file_type_accepted(
                    dropped_files[0]?.type,
                    acceptedFileTypes,
                  )
                ) {
                  show_files(dropped_files);
                } else {
                  $form.addClass('is-error');
                  $error_msg.text('Invalid file type. Please upload an image (PNG, GIF, JPEG).');
                }
              }
            }
          });
      }

      // Handle upload button clicks.
      $upload_button.on('click', function (e) {
        $upload_button.attr('disabled', true).fadeOut('slow');
        $form.trigger('submit');
      });

      // Handle upload form submissions.
      $form.on('submit', function (e) {
        // Prevent duplicate submissions, if the current one is in progress
        if ($form.hasClass('is-uploading')) return false;

        // Switch to uploading state.
        $form.addClass('is-uploading').removeClass('is-error');

        // Proceed with selected file upload.
        if (is_advanced_upload) {
          // ajax file upload for modern browsers
          e.preventDefault();

          // Gather selected form file data, accordingly, based on selection approach.
          let ajax_data = null;
          if (dropped_files) {
            ajax_data = new FormData();
            $.each(dropped_files, function (i, file) {
              ajax_data.append($input.attr('name'), file);
            });
          } else {
            ajax_data = new FormData($form.get(0));
          }

          // Capture additional processing settings.
          ajax_data.append('meta_key', meta_key);
          ajax_data.append('key_prefix', key_prefix);

          // Push selected fields across to backend endpoint.
          $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method'),
            data: ajax_data,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: (xhr) => {
              xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            complete: function () {
              $form.removeClass('is-uploading');
            },
            success: function (response) {
              console.log(response);
              if (response && response?.uploaded === true) {
                $form.addClass('is-success').fadeIn('slow', function () {
                  window.location.reload();
                });
              } else {
                $form.addClass('is-error');
                $error_msg.text(
                  escape(
                    response?.uploaded_msg || translations.error_msg,
                  ),
                );
              }
            },
            error: function (err) {
              console.log(err);
              $form.addClass('is-error');
              $error_msg.text(
                escape(
                  translations.error_msg,
                ),
              );
            },
          });
        }
      });

      // Handle form restart states.
      $restart.on('click', function (e) {
        e.preventDefault();
        $form.removeClass('is-error is-success');
        $input.trigger('click');
      });

      // Firefox focus bug fix for file input.
      $input
        .on('focus', function () {
          $input.addClass('has-focus');
        })
        .on('blur', function () {
          $input.removeClass('has-focus');
        });

      // Support file deletion requests.
      if ($delete_button) {
        $delete_button.on('click', function (e) {
          e.preventDefault();
          if (
            confirm(
              `${escape(translations.delete_msg)}`,
            )
          ) {
            // Hijack the existing form uploading state and disable delete button.
            $form.addClass('is-uploading').removeClass('is-error');
            $delete_button.attr('disabled', true);

            const payload = {
              meta_key: meta_key,
            };

            $.ajax({
              type: 'POST',
              contentType: 'application/json; charset=utf-8',
              dataType: 'json',
              cache: false,
              data: JSON.stringify(payload),
              url: `${restUrl + 'zume_coaching/v1/users/' + user_id + '/storage_delete'}`,
              beforeSend: (xhr) => {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
              },
              complete: function () {
                $form.removeClass('is-uploading');
              },
              success: function (response) {
                if (response && response?.deleted === true) {
                  $delete_button.text(
                    escape(
                      translations.delete_success_msg,
                    ),
                  );
                  window.location.reload();
                } else {
                  $delete_button.text(
                    escape(
                      response?.error || translations.delete_error_msg,
                    ),
                  );
                  $delete_button.attr('disabled', false);
                }
              },
              error: function (err) {
                console.log(err);
                $form.addClass('is-error');
                $error_msg.text(
                  escape(
                    translations.delete_error_msg,
                  ),
                );
              },
            });
          }
        });
      }
    });
  }

  }); // End document.ready
})(jQuery);

