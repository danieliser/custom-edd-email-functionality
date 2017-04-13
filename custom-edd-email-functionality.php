<?php
/*
Plugin Name: Custom EDD Email Functionality
Plugin URI:
Description:
Version:
Author:
Author URI:
License:
License URI:
*/

function custom_edd_email_styles( $which ) {
	ob_start();

	switch( $which ) {
		default:
		case 'receipt':
			$styles = include 'receipt.css';
			break;
	}

	return ob_get_clean();
}


add_filter( 'edd_email_templates', function ( $templates = array() ) {
	return array_merge( $templates, array(
		'pum_mymail' => __( 'Popup Maker (using MyMail)', 'custom' ),
	) );
} );


class Custom_EDD_Purchase_Receipts {

	private static $payment;
	private static $meta;
	private static $cart_for_receipt;
	private static $cart_for_downloads;
	private static $user;
	private static $email;
	private static $status;
	private static $payment_id;

	public static function init() {
		add_filter( 'edd_email_tags', function ( $email_tags = array() ) {
			return array_merge( $email_tags, array(
				array(
					'tag'         => 'custom_receipt',
					'description' => __( 'A custom receipt with download links for each download purchased with licenses, doc links & thumbnails.', 'custom' ),
					'function'    => 'text/html' == EDD()->emails->get_content_type() ? array(
						__CLASS__,
						'build_receipt',
					) : array( __CLASS__, 'build_receipt_plain' ),
				),
			) );
		} );
	}

	public static function build_receipt( $payment_id ) {


		/** @var EDD_Payment $payment */
		static::$payment_id         = $payment_id;
		static::$payment            = new EDD_Payment( $payment_id );
		static::$meta               = edd_get_payment_meta( $payment_id );
		static::$cart_for_receipt   = edd_get_payment_meta_cart_details( $payment_id );
		static::$cart_for_downloads = edd_get_payment_meta_cart_details( $payment_id, true );
		static::$user               = edd_get_payment_meta_user_info( $payment_id );
		static::$email              = edd_get_payment_user_email( $payment_id );
		static::$status             = edd_get_payment_status( $payment, true );


		$purchased_core_bundle = false;
		$recommended_purchases = false;
		$repurchase_discount   = false;

		foreach ( static::$cart_for_receipt as $item ) {
			if ( $item['id'] == 38995 ) {
				$purchased_core_bundle = true;
			}
		}


		mailster_add_style( 'custom_edd_email_styles', 'receipt' );

		ob_start();

		static::greeting();

		static::download_link_notice();

		static::receipt();

		static::downloads();
		foreach ( static::$cart_for_downloads as $item ) {
			static::item_info( $item );
		}

		static::spacer();

		if ( ! $purchased_core_bundle && $recommended_purchases ) {
			static::thank_you_upsell( $recommended_purchases );
		} elseif ( $repurchase_discount ) {
			static::thank_you_discount( $repurchase_discount );
		} else {
			static::thank_you();
		}

		if ( ! $purchased_core_bundle ) {
			static::core_upgrade();
		}

		static::affiliate_program();

		// static::feedback();

		static::support();

		static::spacer( 36 );

		echo '<hr>';

		$content = ob_get_clean();

		// return $content;

		return mailster()->helper()->prepare_content( $content );
	}

	public static function greeting() { ?>
		<p style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 24px; padding: 0; text-align: left">Greetings <?php echo static::$user['first_name']; ?>,</p>
		<p style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 24px; padding: 0; text-align: left"><strong>Thank you very much for your purchase, and welcome to Popup Maker!</strong></p>
		<p style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 24px; padding: 0; text-align: left">We hope you enjoy our product, and don't forget, if you have any questions or issues, you can always contact us via our <a href="https://wppopupmaker.com/support" target="_blank" style="Margin: 0; color: #9ab927; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none">Support Portal</a> or <a href="http://docs.wppopupmaker.com/" target="_blank" style="Margin: 0; color: #9ab927; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none">Documentation</a>. Enjoy!</p>
		<p style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 24px; padding: 0; text-align: left">The items that you have purchased are listed below - the list includes file download links. These links below will expire.</p><?php
	}

	public static function download_link_notice() { ?>
		<table class="callout" style="Margin-bottom: 16px; border-collapse: collapse; border-spacing: 0; margin-bottom: 16px; padding: 0; text-align: left; vertical-align: top; width: 100%">
			<tr style="padding: 0; text-align: left; vertical-align: top">
				<th class="callout-inner success" style="Margin: 0; background: #c6ffc6; background-color: #9ab927; border: 1px solid #001a00; color: #fefefe; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 10px; text-align: left; width: 100%">
					<p class="text-center" style="Margin: 0; Margin-bottom: 24px; color: #ffffff; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; line-height: 1.3; margin: 0; margin-bottom: 0; padding: 0; text-align: center"><span style="color: #2d2d2d">DOWNLOAD LINKS BELOW EXPIRE IN</span> 72 HOURS</p>
				</th>
				<th class="expander" style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0 !important; text-align: left; visibility: hidden; width: 0"></th>
			</tr>
		</table><?php
		static::spacer( 12 ); ?>
		<table class="callout" style="Margin-bottom: 16px; border-collapse: collapse; border-spacing: 0; margin-bottom: 16px; padding: 0; text-align: left; vertical-align: top; width: 100%">
			<tr style="padding: 0; text-align: left; vertical-align: top">
				<th class="callout-inner general padded" style="Margin: 0; background: #fefefe; background-color: #F3F3F3; border: 1px solid #cbcbcb; box-sizing: content-box; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; max-width: 502px; padding: 24px 24px; text-align: left; width: 100%">
					<p class="text-center" style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 0; padding: 0; text-align: center">You can always access your downloads at any time by logging into your Popup Maker <a href="https://wppopupmaker.com/account/" target="_blank">Account</a> and clicking the Downloads Tab.</p>
				</th>
				<th class="expander" style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0 !important; text-align: left; visibility: hidden; width: 0"></th>
			</tr>
		</table><?php
		static::spacer( 12 );
	}

	public static function receipt() { ?>
		<div class="purchase-receipt">
			<div class="receipt__heading" style="background-color: #9ab927 !important; color: #fff">
				<?php static::spacer( 24 ); ?>
				<h1 class="text-center" style="Margin: 0; Margin-bottom: 10px; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 22px; font-weight: bold; line-height: 26px; margin: 0; margin-bottom: 0; padding: 0; text-align: center; word-wrap: normal">PURCHASE RECEIPT</h1>
				<?php static::spacer( 24 ); ?>
			</div>
			<div class="receipt__subheading" style="background-color: #a8cb29 !important; color: #fff">
				<?php static::spacer( 12 ); ?>
				<h2 class="text-center" style="Margin: 0; Margin-bottom: 10px; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 12px; font-weight: normal; line-height: 14px; margin: 0; margin-bottom: 0; padding: 0; text-align: center; word-wrap: normal">ORDER NUMBER: <?php echo absint( static::$payment_id ); ?></h2>
				<?php static::spacer( 12 ); ?>
			</div>
			<table class="cart-items" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%">
				<thead>
				<tr style="background-color: #2d2d2d; padding: 0; text-align: left; vertical-align: top">
					<td width="433" valign="middle" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #ffffff; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: .5em .75em; text-align: left; vertical-align: top; word-wrap: break-word">PRODUCT</td>
					<td width="167" valign="middle" class="text-right" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #ffffff; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: .5em .75em; text-align: right; vertical-align: top; word-wrap: break-word">PRICE</td>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( static::$cart_for_receipt as $item ) : static::receipt_row( $item ); endforeach; ?>
				</tbody>
				<tfoot>
				<tr style="background-color: #F7f7f7; border-top: 1px solid #DFDFDF; padding: 0; text-align: left; vertical-align: top">
					<td valign="middle" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #2d2d2d; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: .5em .75em; text-align: left; vertical-align: top; word-wrap: break-word">Method</td>
					<td valign="middle" class="text-right" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #2d2d2d; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: .5em .75em; text-align: right; vertical-align: top; word-wrap: break-word"><?php echo edd_get_gateway_checkout_label( edd_get_payment_gateway( static::$payment_id ) ); ?></td>
				</tr>
				<?php if ( isset( static::$user['discount'] ) && static::$user['discount'] != 'none' ) : ?>
					<tr style="background-color: #F7f7f7; border-top: 1px solid #DFDFDF; padding: 0; text-align: left; vertical-align: top">
						<td valign="middle" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #2d2d2d; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: .5em .75em; text-align: left; vertical-align: top; word-wrap: break-word">Discount(s)</td>
						<td valign="middle" class="text-right" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #2d2d2d; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: .5em .75em; text-align: right; vertical-align: top; word-wrap: break-word"><?php echo static::$user['discount']; ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( edd_use_taxes() ) : ?>
					<tr style="background-color: #F7f7f7; border-top: 1px solid #DFDFDF; padding: 0; text-align: left; vertical-align: top">
						<td valign="middle" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #2d2d2d; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: .5em .75em; text-align: left; vertical-align: top; word-wrap: break-word">Tax</td>
						<td valign="middle" class="text-right" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #2d2d2d; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: .5em .75em; text-align: right; vertical-align: top; word-wrap: break-word"><?php echo edd_payment_tax( static::$payment_id ); ?></td>
					</tr>
				<?php endif; ?>
				<tr class="total" style="background-color: #F7f7f7; border-top: 1px solid #DFDFDF; padding: 0; text-align: left; vertical-align: top">
					<td valign="middle" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-bottom: 2px solid #DFDFDF; border-collapse: collapse !important; color: #2d2d2d; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; hyphens: auto; line-height: 1.3; margin: 0; padding: 1em .75em; text-align: left; vertical-align: top; word-wrap: break-word">Total</td>
					<td valign="middle" class="text-right" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-bottom: 2px solid #DFDFDF; border-collapse: collapse !important; color: #2d2d2d; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; hyphens: auto; line-height: 1.3; margin: 0; padding: 1em .75em; text-align: right; vertical-align: top; word-wrap: break-word"><?php echo edd_payment_amount( static::$payment_id ); ?></td>
				</tr>
				</tfoot>
			</table>
		</div><?php
	}

	public static function downloads() {
		if ( static::$cart_for_downloads ) :
			$_license_keys = edd_software_licensing()->get_licenses_of_purchase( static::$payment_id );
			$license_keys = array();
			if ( $_license_keys ) {
				foreach ( $_license_keys as $license ) {
					$license_keys[ (int) $license->download_id ] = $license->key;
				}
			}

			foreach ( static::$cart_for_downloads as $item ) :
				$price_id = edd_get_cart_item_price_id( $item );
				$title = get_the_title( $item['id'] );
				$title_with_option = $title;
				if ( $price_id !== null ) {
					$title_with_option .= "&nbsp;&ndash;&nbsp;" . edd_get_price_option_name( $item['id'], $price_id, static::$payment_id );
				}
				$title             = apply_filters( 'edd_email_receipt_download_title', $title, $item, $price_id, static::$payment_id );
				$title_with_option = apply_filters( 'edd_email_receipt_download_title', $title_with_option, $item, $price_id, static::$payment_id );
				$download_link = false;
				$files = edd_get_download_files( $item['id'], $price_id );
				if ( ! empty( $files ) ) {
					foreach ( $files as $filekey => $file ) {
						$download_link = edd_get_download_file_url( static::$meta['key'], static::$email, $filekey, $item['id'], $price_id );
						break;
					}
				}
				static::item_info( array(
					'id'                => $item['id'],
					'image_url'         => get_the_post_thumbnail_url( $item['id'], 'featured' ),
					'title'             => $title,
					'title_with_option' => $title_with_option,
					'download_link'     => $download_link,
					'doc_link'          => get_post_meta( $item['id'], '_download_docs_url', true ),
					'install_link'      => 'http://docs.wppopupmaker.com/category/23-getting-started',
					'license_key'       => isset( $license_keys[ $item['id'] ] ) ? $license_keys[ $item['id'] ] : '',
					'notes'             => edd_get_product_notes( $item['id'] ),
				) );
			endforeach;
		endif;
	}

	public static function item_info( $item ) {
		if ( empty( $item['title_with_option'] ) || empty( $item['download_link'] ) || empty( $item['license_key'] ) ) {
			return;
		}
		?><div class="licensed-item" style="background-color: #F3F3F3; padding: 12px">
			<table class="row" style="border-collapse: collapse; border-spacing: 0; display: table; padding: 0; position: relative; text-align: left; vertical-align: top; width: 100%">
				<tbody>
				<tr style="padding: 0; text-align: left; vertical-align: top">
					<th class="text-center small-12 large-4 columns first" style="Margin: 0 auto; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1; margin: 0 auto; padding: 0; padding-bottom: 0; padding-left: 0 !important; padding-right: 0 !important; text-align: center; width: 33.33333%">
						<table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%"><tr style="padding: 0; text-align: left; vertical-align: top"><th style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left"><img class="" border="0" src="<?php echo esc_url( $item['image_url'] ); ?>" alt="<?php esc_attr_e( $item['title'] ); ?>" title="<?php esc_attr_e( $item['title'] ); ?>" style="-ms-interpolation-mode: bicubic; clear: both; display: block; max-width: 166.6666667px; outline: none; text-decoration: none; width: auto" width="166" height="100"></th></tr></table>
					</th>
					<th class="small-12 large-8 columns last" style="Margin: 0 auto; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1; margin: 0 auto; padding: 0; padding-bottom: 0; padding-left: 0 !important; padding-right: 0 !important; text-align: left; width: 66.66667%">
						<table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%"><tr style="padding: 0; text-align: left; vertical-align: top"><th style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left"><div class="licensed-item-info" style="padding: 0 12px"><p class="title" style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 19px; margin-bottom: 12px; padding: 0; text-align: left"><?php echo $item['title_with_option']; ?></p><?php static::spacer( 12 ); ?><table class="menu" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%"><tr style="padding: 0; text-align: left; vertical-align: top"><td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word"><table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%"><tr style="padding: 0; text-align: left; vertical-align: top"><th class="menu-item float-center" style="Margin: 0 auto; color: #626262; float: none; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 10px; padding-bottom: 0; padding-left: 0; padding-right: 10px; padding-top: 0; position: relative; text-align: center"><a href="<?php echo esc_url( $item['download_link'] ); ?>" style="Margin: 0; color: #9ab927; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none"><small style="color: inherit; font-size: 80%">DOWNLOAD</small></a></th><th class="menu-item float-center" style="Margin: 0 auto; color: #626262; float: none; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 10px; padding-bottom: 0; padding-right: 10px; padding-top: 0; position: relative; text-align: center"><a href="<?php echo esc_url( $item['doc_link'] ); ?>" style="Margin: 0; color: #9ab927; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none"><small style="color: inherit; font-size: 80%">DOCUMENTATION</small></a></th><th class="menu-item float-center" style="Margin: 0 auto; color: #626262; float: none; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 10px; padding-bottom: 0; padding-right: 0; padding-top: 0; position: relative; text-align: center"><a href="<?php echo esc_url( $item['install_link'] ); ?>" style="Margin: 0; color: #9ab927; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none"><small style="color: inherit; font-size: 80%">INSTALLATION</small></a></th></tr></table></td></tr></table><?php static::spacer( 12 ); ?><p style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 24px; padding: 0; text-align: left"><small style="color: #626262; font-size: 80%"><strong>Extension Key</strong>: <?php echo $item['license_key']; ?></small></p><?php if ( ! empty( $item['notes'] ) ) : ?><p style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 24px; padding: 0; text-align: left"><small style="color: #626262; font-size: 80%"><strong>Notes</strong>: <?php echo $item['notes']; ?></small></p><?php endif; ?></div></th></tr></table>
					</th>
				</tr>
				</tbody>
			</table>
		</div><?php
	}

	public static function spacer( $size = 24 ) { ?>
		<table class="spacer" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%"><tbody><tr style="padding: 0; text-align: left; vertical-align: top"><td height="<?php echo absint( $size ); ?>px" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: <?php echo absint( $size ); ?>px; font-weight: normal; hyphens: auto; line-height: <?php echo absint( $size ); ?>px; margin: 0; mso-line-height-rule: exactly; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word">&#xA0;</td></tr></tbody></table><?php
	}

	public static function thank_you_upsell( $items ) { ?>
		<table class="callout" style="Margin-bottom: 16px; border-collapse: collapse; border-spacing: 0; margin-bottom: 16px; padding: 0; text-align: left; vertical-align: top; width: 100%">
			<tr style="padding: 0; text-align: left; vertical-align: top">
				<th class="callout-inner padded" style="Margin: 0; background: #fefefe; border: 1px solid #cbcbcb; box-sizing: content-box; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; max-width: 502px; padding: 24px 24px; text-align: left; width: 100%">
					<h1 class="text-center" style="Margin: 0; Margin-bottom: 10px; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; margin-top: 0; padding: 0; text-align: center; word-wrap: normal">Thank you for shopping with us!</h1>
					<p class="text-center" style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 0; padding: 0; text-align: center">We appreciate your purchase today - feel free to check out our other downloads in the Popup Maker Marketplace.</p>
					<?php static::spacer( 12 ); ?>
					<center data-parsed="" style="min-width: 528px; width: 100%">
						<table class="button expanded float-center" style="Margin: 0 0 16px 0; border-collapse: collapse; border-spacing: 0; float: none; margin: 0 0 16px 0; margin-bottom: 0; padding: 0; text-align: center; vertical-align: top; width: 100% !important">
							<tr style="padding: 0; text-align: left; vertical-align: top">
								<td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word">
									<table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%">
										<tr style="padding: 0; text-align: left; vertical-align: top">
											<td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; background: #9ab927; border: 2px solid #9ab927; border-collapse: collapse !important; color: #fefefe; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word"><center data-parsed="" style="min-width: 0; width: 100%"><a href="https://wppopupmaker.com/extensions/" target="_blank" align="center" class="float-center" style="Margin: 0; border: 0 solid #9ab927; border-radius: 3px; color: #fefefe; display: inline-block; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; line-height: 1.3; margin: 0; padding: 8px 16px 8px 16px; padding-left: 0; padding-right: 0; text-align: center; text-decoration: none; width: 100%">Visit Our Store</a></center></td>
										</tr>
									</table>
								</td>
								<td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0 !important; text-align: left; vertical-align: top; visibility: hidden; width: 0; word-wrap: break-word"></td>
							</tr>
						</table>
					</center>
				</th>
				<th class="expander" style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0 !important; text-align: left; visibility: hidden; width: 0"></th>
			</tr>
		</table><?php
	}

	public static function thank_you_discount( $discount ) { ?>
		<table class="callout" style="Margin-bottom: 16px; border-collapse: collapse; border-spacing: 0; margin-bottom: 16px; padding: 0; text-align: left; vertical-align: top; width: 100%">
			<tr style="padding: 0; text-align: left; vertical-align: top">
				<th class="callout-inner padded" style="Margin: 0; background: #fefefe; border: 1px solid #cbcbcb; box-sizing: content-box; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; max-width: 502px; padding: 24px 24px; text-align: left; width: 100%">
					<h1 class="text-center" style="Margin: 0; Margin-bottom: 10px; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; margin-top: 0; padding: 0; text-align: center; word-wrap: normal">Thank you for shopping with us!</h1>
					<p class="text-center" style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 0; padding: 0; text-align: center">As token of our gratitude, we invite you to use the following promo code to get <!-- edit here -->{discount_percentage}% off your next purchase or upgrade!</p>
					<?php static::spacer( 12 ); ?>
					<table class="button expanded" style="Margin: 0 0 16px 0; border-collapse: collapse; border-spacing: 0; margin: 0 0 16px 0; margin-bottom: 0; padding: 0; text-align: left; vertical-align: top; width: 100% !important">
						<tr style="padding: 0; text-align: left; vertical-align: top">
							<td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word">
								<table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%">
									<tr style="padding: 0; text-align: left; vertical-align: top">
										<td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; background: #9ab927; border: 2px solid #9ab927; border-collapse: collapse !important; color: #fefefe; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word"><center data-parsed="" style="min-width: 0; width: 100%"><a href="https://wppopupmaker.com/extensions/?discount={discount_code}<!-- edit here -->" target="_blank" align="center" class="float-center" style="Margin: 0; border: 0 solid #9ab927; border-radius: 3px; color: #fefefe; display: inline-block; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; line-height: 1.3; margin: 0; padding: 8px 16px 8px 16px; padding-left: 0; padding-right: 0; text-align: center; text-decoration: none; width: 100%"><!-- edit here -->{discount_code}</a></center></td>
									</tr>
								</table>
							</td>
							<td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0 !important; text-align: left; vertical-align: top; visibility: hidden; width: 0; word-wrap: break-word"></td>
						</tr>
					</table>
				</th>
				<th class="expander" style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0 !important; text-align: left; visibility: hidden; width: 0"></th>
			</tr>
		</table><?php
	}

	public static function thank_you() { ?>
		<table class="callout" style="Margin-bottom: 16px; border-collapse: collapse; border-spacing: 0; margin-bottom: 16px; padding: 0; text-align: left; vertical-align: top; width: 100%">
			<tr style="padding: 0; text-align: left; vertical-align: top">
				<th class="callout-inner padded" style="Margin: 0; background: #fefefe; border: 1px solid #cbcbcb; box-sizing: content-box; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; max-width: 502px; padding: 24px 24px; text-align: left; width: 100%">
					<h1 class="text-center" style="Margin: 0; Margin-bottom: 10px; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; margin-top: 0; padding: 0; text-align: center; word-wrap: normal">Thank you for shopping with us!</h1>
					<p class="text-center" style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 0; padding: 0; text-align: center">We appreciate your purchase today - feel free to check out our other downloads in the Popup Maker Marketplace.</p><?php
					static::spacer( 12 ); ?>
					<center data-parsed="" style="min-width: 100%; width: 100%"><table class="button expanded float-center" style="Margin: 0 0 16px 0; border-collapse: collapse; border-spacing: 0; float: none; margin: 0 0 16px 0; margin-bottom: 0; padding: 0; text-align: center; vertical-align: top; width: 100% !important">
						<tr style="padding: 0; text-align: left; vertical-align: top">
							<td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word">
								<table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%">
									<tr style="padding: 0; text-align: left; vertical-align: top">
										<td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; background: #9ab927; border: 2px solid #9ab927; border-collapse: collapse !important; color: #fefefe; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word"><center data-parsed="" style="min-width: 0; width: 100%"><a href="https://wppopupmaker.com/extensions/" target="_blank" align="center" class="float-center" style="Margin: 0; border: 0 solid #9ab927; border-radius: 3px; color: #fefefe; display: inline-block; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; line-height: 1.3; margin: 0; padding: 8px 16px 8px 16px; padding-left: 0; padding-right: 0; text-align: center; text-decoration: none; width: 100%">Visit Our Store</a></center></td>
									</tr>
								</table>
							</td>
							<td class="expander" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0 !important; text-align: left; vertical-align: top; visibility: hidden; width: 0; word-wrap: break-word"></td>
						</tr>
					</table></center>
				</th>
				<th class="expander" style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0 !important; text-align: left; visibility: hidden; width: 0"></th>
			</tr>
		</table><?php
	}

	public static function core_upgrade() { ?>
		<table class="callout" style="background-color: #2d2d2d; color: #fff; Margin-bottom: 16px; border-collapse: collapse; border-spacing: 0; margin-bottom: 16px; padding: 0; text-align: left; vertical-align: top; width: 100%">
			<tr style="padding: 0; text-align: left; vertical-align: top">
				<th class="callout-inner black" style="Margin: 0; background: transparent; border: 1px solid #cbcbcb; color: #fff; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 10px; text-align: left; width: 100%">
					<center data-parsed="" style="min-width: 528px; width: 100%"><img border="0" src="https://email.wppopupmaker.com/wp-content/uploads/2017/04/core-upgrade.png" alt="Core Extension Bundle" title="Core Extension Bundle" width="172" align="center" class="float-center" style="-ms-interpolation-mode: bicubic; clear: both; display: block; max-width: 100%; outline: none; text-decoration: none; width: auto"></center>
					<h1 class="text-center" style="Margin: 0; Margin-bottom: 10px; color: #fff; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; padding: 0; text-align: center; word-wrap: normal">Upgrade at any time!</h1>
					<p class="text-center" style="Margin: 0; Margin-bottom: 24px; color: #fff; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 0; padding: 0; text-align: center">Upgrade your current license at any time and only pay the difference! Get every extension in our marketplace with unlimited sites by upgrading to the Core Extensions Bundle.</p>
					<?php static::spacer( 12 ); ?>
					<center data-parsed="" style="min-width: 528px; width: 100%"><a class="float-center" href="http://docs.wppopupmaker.com/article/62-can-i-buy-license-and-upgrade-later" target="_blank" align="center" style="Margin: 0; color: #fff; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none">LEARN MORE</a></center>
				</th>
				<th class="expander" style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0 !important; text-align: left; visibility: hidden; width: 0"></th>
			</tr>
		</table><?php
	}

	public static function affiliate_program() { ?>
		<div class="affiliate__heading" style="background-color: #a8cb29; background-image: url('https://email.wppopupmaker.com/wp-content/uploads/2017/04/affiliate-heading.jpg'); color: #fff"><?php static::spacer( 24 ); ?><h1 class="text-center" style="Margin: 0; Margin-bottom: 10px; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 0; padding: 0; text-align: center; word-wrap: normal">Make cash selling Popup Maker</h1><?php static::spacer( 24 ); ?></div>
		<table class="callout" style="Margin-bottom: 16px; border-collapse: collapse; border-spacing: 0; margin-bottom: 16px; padding: 0; text-align: left; vertical-align: top; width: 100%"><tr style="padding: 0; text-align: left; vertical-align: top"><th class="callout-inner padded" style="Margin: 0; background: #fefefe; border: 1px solid #cbcbcb; box-sizing: content-box; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; max-width: 502px; padding: 24px 24px; text-align: left; width: 100%"><p class="text-center" style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 0; padding: 0; text-align: center">Now that you own a Popup Maker License, you're qualified to apply for our Affiliate Program - join our forces today!</p><?php static::spacer( 12 ); ?><center data-parsed="" style="min-width: 528px; width: 100%"><a class="float-center" href="https://wppopupmaker.com/affiliates/" target="_blank" align="center" style="Margin: 0; color: #9ab927; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none">LEARN MORE</a></center></th><th class="expander" style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0 !important; text-align: left; visibility: hidden; width: 0"></th></tr></table><?php
	}

	public static function feedback() { ?>
		<table class="callout" style="Margin-bottom: 16px; border-collapse: collapse; border-spacing: 0; margin-bottom: 16px; padding: 0; text-align: left; vertical-align: top; width: 100%">
			<tr style="padding: 0; text-align: left; vertical-align: top">
				<th class="callout-inner general no-border padded" style="Margin: 0; background: #fefefe; background-color: #F3F3F3; border: 0 !important; box-sizing: content-box; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; max-width: 502px; padding: 24px 24px; text-align: left; width: 100%">
					<h2 class="text-center" style="Margin: 0; Margin-bottom: 10px; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 18px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; padding: 0; text-align: center; word-wrap: normal">We want your feedback!</h2>
					<?php static::spacer( 12 ); ?>
					<p class="text-center" style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 0; padding: 0; text-align: center"><small style="color: #626262; font-size: 80%">We value your opinion and are always on the look out for ways to improve our service. Click below to leave feedback on your experience.</small></p>
					<?php static::spacer( 24 ); ?>
					<table class="row image-links" style="border-collapse: collapse; border-spacing: 0; display: table; padding: 0; position: relative; text-align: left; vertical-align: top; width: 100%">
						<tbody>
						<tr style="padding: 0; text-align: left; vertical-align: top">
							<th class="small-12 large-4 columns first" style="Margin: 0 auto; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 0; padding-bottom: 0; padding-left: 0 !important; padding-right: 0 !important; text-align: left; width: 33.33333%">
								<table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%">
									<tr style="padding: 0; text-align: left; vertical-align: top">
										<th style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left"></th>
									</tr>
								</table>
							</th>
							<th class="small-12 large-4 columns" style="Margin: 0 auto; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 0; padding-bottom: 0; padding-left: 0 !important; padding-right: 0 !important; text-align: left; width: 33.33333%">
								<table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%">
									<tr style="padding: 0; text-align: left; vertical-align: top">
										<th style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left">
											<table class="row image-links" style="border-collapse: collapse; border-spacing: 0; display: table; padding: 0; position: relative; text-align: left; vertical-align: top; width: 100%">
												<tbody>
												<tr style="padding: 0; text-align: left; vertical-align: top">
													<th class="small-12 large-6 columns first" style="Margin: 0 auto; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 0; padding-bottom: 0; padding-left: 0 !important; padding-right: 0 !important; text-align: left; width: 50%">
														<table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%">
															<tr style="padding: 0; text-align: left; vertical-align: top">
																<th style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left">
																	<p class="text-center" style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 0; padding: 0; text-align: center"><a class="" href="#" target="_blank" style="Margin: 0; color: #9ab927; display: inline-block; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none"><img border="0" src="https://email.wppopupmaker.com/wp-content/uploads/2017/04/like.png" alt="Like" title="Like" width="51" style="-ms-interpolation-mode: bicubic; border: none; clear: both; display: block; max-width: 100%; outline: none; text-decoration: none; width: auto"></a></p>
																</th>
															</tr>
														</table>
													</th>
													<th class="small-12 large-6 columns last" style="Margin: 0 auto; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 0; padding-bottom: 0; padding-left: 0 !important; padding-right: 0 !important; text-align: left; width: 50%">
														<table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%">
															<tr style="padding: 0; text-align: left; vertical-align: top">
																<th style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left">
																	<p class="text-center" style="Margin: 0; Margin-bottom: 24px; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 0; padding: 0; text-align: center"><a class="" href="#" target="_blank" style="Margin: 0; color: #9ab927; display: inline-block; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none"><img border="0" src="https://email.wppopupmaker.com/wp-content/uploads/2017/04/dislike.png" alt="Dislike" title="Dislike" width="51" style="-ms-interpolation-mode: bicubic; border: none; clear: both; display: block; max-width: 100%; outline: none; text-decoration: none; width: auto"></a> </p>
																</th>
															</tr>
														</table>
													</th>
												</tr>
												</tbody>
											</table>
										</th>
									</tr>
								</table>
							</th>
							<th class="small-12 large-4 columns last" style="Margin: 0 auto; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 0; padding-bottom: 0; padding-left: 0 !important; padding-right: 0 !important; text-align: left; width: 33.33333%">
								<table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%">
									<tr style="padding: 0; text-align: left; vertical-align: top">
										<th style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left"></th>
									</tr>
								</table>
							</th>
						</tr>
						</tbody>
					</table>
				</th>
				<th class="expander" style="Margin: 0; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0 !important; text-align: left; visibility: hidden; width: 0"></th>
			</tr>
		</table><?php
	}

	public static function support() { ?>
		<div class="support-footer">
			<h2 class="text-center" style="Margin: 0; Margin-bottom: 10px; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 20px; font-weight: bold; line-height: 1.3; margin: 0; margin-bottom: 5px; padding: 0; text-align: center; word-wrap: normal">Popup Maker for WordPress</h2>
			<h3 class="text-center" style="Margin: 0; Margin-bottom: 10px; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 18px; font-weight: normal; line-height: 1.3; margin: 0; margin-bottom: 10px; padding: 0; text-align: center; word-wrap: normal">support@wppopupmaker.com</h3>
			<?php static::spacer( 12 ); ?>
			<center data-parsed="" style="min-width: 528px; width: 100%">
				<table align="center" class="menu float-center" style="Margin: 0 auto; border-collapse: collapse; border-spacing: 0; float: none; margin: 0 auto; padding: 0; text-align: center; vertical-align: top; width: auto !important">
					<tr style="padding: 0; text-align: left; vertical-align: top">
						<td style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #626262; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word">
							<table style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%">
								<tr style="padding: 0; text-align: left; vertical-align: top">
									<th class="menu-item float-center" style="Margin: 0 auto; color: #626262; float: none; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 10px; padding-bottom: 0; padding-left: 0; padding-right: 10px; padding-top: 0; position: relative; text-align: center"><a href="https://wppopupmaker.com/account/" style="Margin: 0; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 13px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none">ACCOUNT</a></th>
									<th class="menu-item float-center" style="Margin: 0 auto; color: #626262; float: none; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 10px; padding-bottom: 0; padding-right: 10px; padding-top: 0; position: relative; text-align: center"><a href="https://docs.wppopupmaker.com/" style="Margin: 0; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 13px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none">DOCUMENTATION</a></th>
									<th class="menu-item float-center" style="Margin: 0 auto; color: #626262; float: none; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 10px; padding-bottom: 0; padding-right: 10px; padding-top: 0; position: relative; text-align: center"><a href="https://wppopupmaker.com/blog/" style="Margin: 0; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 13px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none">BLOG</a></th>
									<th class="menu-item float-center" style="Margin: 0 auto; color: #626262; float: none; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 1.3; margin: 0 auto; padding: 10px; padding-bottom: 0; padding-right: 0; padding-top: 0; position: relative; text-align: center"><a href="https://wppopupmaker.com/support/" style="Margin: 0; color: inherit; font-family: Helvetica, Arial, sans-serif; font-size: 13px; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none">SUPPORT</a></th>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</center>
		</div><?php
	}

	public static function receipt_row( $item ) {
		$price_id = edd_get_cart_item_price_id( $item );

		$title = get_the_title( $item['id'] );

		if ( $price_id !== null ) {
			$title .= "&nbsp;&ndash;&nbsp;" . edd_get_price_option_name( $item['id'], $price_id, static::$payment_id );
		}

		$title = apply_filters( 'edd_email_receipt_download_title', $title, $item, $price_id, static::$payment_id );

		$amount = edd_get_price_option_amount( $item['id'], $price_id ); ?>
		<tr style="background-color: #F3F3F3; border-bottom: 1px dotted #DFDFDF; color: #71777D; padding: 0; text-align: left; vertical-align: top">
			<td valign="middle" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #71777D; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: .5em .75em; text-align: left; vertical-align: top; word-wrap: break-word"><?php echo $title; ?></td>
			<td valign="middle" class="text-right" style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #71777D; font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 1.3; margin: 0; padding: .5em .75em; text-align: right; vertical-align: top; word-wrap: break-word">$<?php echo $amount; ?></td>
		</tr>
		<?php
	}

}

add_action( 'plugins_loaded', 'Custom_EDD_Purchase_Receipts::init' );