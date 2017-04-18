jQuery( function( $ ) {
	'use strict';
	var mg_eftsecure_form = {

		init: function( form ) {
			eftSec.checkout.settings.serviceUrl = "{protocol}://eftsecure.callpay.com/eft";
			this.form          = form;
			this.eftsecure_submit = false;

			$( this.form )
				.on( 'click', '#place_order', this.onSubmit );
		},

		isEftsecureChosen: function() {
			//return $( '#payment_method_eftsecure' ).is( ':checked' );
			return true;
		},

		isEftsecureModalNeeded: function( e ) {
			// Don't affect submit if modal is not needed.
			if (!mg_eftsecure_form.isEftsecureChosen() ) {
				//return false;
			}
            // Don't affect submit if payment already complete.
			if (mg_eftsecure_form.eftsecure_submit) {
				//return false;
			}
			return true;
		},

		block: function() {
			mg_eftsecure_form.form.block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
		},

		unblock: function() {
			mg_eftsecure_form.form.unblock();
		},

		onClose: function() {
			mg_eftsecure_form.unblock();
		},

		onSubmit: function( e ) {
			if ( mg_eftsecure_form.isEftsecureModalNeeded()) {
				//var $data = jQuery('#eftsecure-payment-data');
				e.preventDefault();

				mg_eftsecure_form.block();
				eftSec.checkout.init({
					organisation_id: mg_eftsecure_params.organisation_id,
					token: mg_eftsecure_params.token,
					reference: mg_eftsecure_params.reference,
					primaryColor: mg_eftsecure_params.pcolor,
					secondaryColor: mg_eftsecure_params.scolor,
					amount: mg_eftsecure_params.amount,
                    onLoad: function() {
						mg_eftsecure_form.unblock();
					},
                    onComplete: function(data) {
                        eftSec.checkout.hideFrame();
                        console.log('Transaction Completed');
                        mg_eftsecure_form.eftsecure_submit = true;
                        var $form = mg_eftsecure_form.form;
                        if ($form.find( 'input.eftsecure_transaction_id' ).length > 0) {
                            $form.find('input.eftsecure_transaction_id').remove();
                        }
                        $form.append( '<input type="hidden" class="eftsecure_transaction_id" name="eftsecure_transaction_id" value="' + data.transaction_id + '"/>' );
                        $form.submit();
                    }
				});

				return false;
			}

			return true;
		},

		resetModal: function() {
			if (mg_eftsecure_form.form.find( 'input.eftsecure_transaction_id' ).length > 0) {
                mg_eftsecure_form.form.find('input.eftsecure_transaction_id').remove();
            }
			mg_eftsecure_form.eftsecure_submit = false;
		}
	};

	mg_eftsecure_form.init( $( "form#add_payment" ) );
} );