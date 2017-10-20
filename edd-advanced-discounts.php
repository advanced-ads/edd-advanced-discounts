<?php
/**
* Plugin Name: EDD Advanced Discounts
* Plugin URI: https://webgilde.com
* Text Domain: edd-advanced-discounts
* Domain Path: languages
* Description: Create advanced discount code conditions
* Author: Thomas Maier
* Version: 1.0
* Author URI: https://webgilde.com
*/

/**
 * add our custom options to discount page
 */
add_action( 'edd_add_discount_form_before_start', 'edd_advanced_discounts_exclude_lifetime_field' );
add_action( 'edd_edit_discount_form_before_start', 'edd_advanced_discounts_exclude_lifetime_field', 10, 2 );
function edd_advanced_discounts_exclude_lifetime_field( $discount_id = false, $discount = false ){
        // checkbox for option to exclude lifetime licenses from discounts
        $exclude_lifetime = ( $discount instanceof EDD_Discount ) ? $discount->get_meta( 'exclude_lifetime', true ) : false;
        ?><tr>
                <th scope="row" valign="top">
                        <label for="edd-exclude-lifetime"><?php _e( 'Exclude Lifetime', 'edd-advanced-discounts' ); ?></label>
                </th>
                <td>
                        <input type="checkbox" id="edd-exclude-lifetime" name="exclude_lifetime" value="1"<?php checked( true, $exclude_lifetime ); ?>/>
                        <span class="description"><?php _e( 'Exclude lifetime pricing options from this discount.', 'edd-advanced-discounts' ); ?></span>
                </td>
        </tr><?php
        // input field for IDs of price options that should accept the discount
        $price_options = ( $discount instanceof EDD_Discount ) ? implode( ',', $discount->get_meta( 'price_options', true ) ) : '';
        ?><tr>
                <th scope="row" valign="top">
                        <label for="edd-price-options"><?php _e( 'Price Options', 'edd-advanced-discounts' ); ?></label>
                </th>
                <td>
                        <input type="input" id="edd-price-options" name="price_options" value="<?php echo $price_options; ?>"/>
                        <span class="description"><?php _e( 'Enter comma separated list of price option IDs that can accept this discount.', 'edd-advanced-discounts' ); ?></span>
                </td>
        </tr><?php
}

/**
 * save our custom options when discount is saved or updated
 */
add_filter( 'edd_insert_discount', 'edd_advanced_discounts_add_meta' );
add_filter( 'edd_update_discount', 'edd_advanced_discounts_add_meta' );
function edd_advanced_discounts_add_meta( $meta ){
        
        if( isset( $_POST['exclude_lifetime'] ) ){
            $meta['exclude_lifetime'] = 1;
        } else {
            $meta['exclude_lifetime'] = 0;
        }
        if( isset( $_POST['price_options'] ) ){
                $meta['price_options'] = trim( $_POST['price_options'] ) ? explode( ',', $_POST['price_options'] ) : '';
        }
        return $meta;
}

/**
 * validate discount conditions when discount is used
 */
add_filter( 'edd_is_discount_valid', 'edd_advanced_discounts_is_discount_valid', 10, 4 );
function edd_advanced_discounts_is_discount_valid( $return, $discount_id, $discount_code, $user ){
        // don’t bother to validate if is false already
        if( ! $return ){
            return $return;
        }
        
        $discount = edd_get_discount( $discount_id );
        $cart_content = edd_get_cart_contents();
        
        if( $discount instanceof EDD_Discount ){
                
                // check exclude lifetime
                if( $exclude_lifetime = $discount->get_meta( 'exclude_lifetime', true ) ){
                        // iterate through cart items
                        foreach( $cart_content as $_item ){
                                $download = new EDD_SL_Download( $_item['id'] );
                                $is_lifetime = isset( $_item['options']['price_id'] ) ? $download->is_price_lifetime( $_item['options']['price_id'] ) : false;
                        }

                        if( $is_lifetime && $exclude_lifetime ){
                                edd_set_error( 'edd-discount-error', _x( 'This discount can only be used for non-lifetime products.', 'error shown when attempting to use a discount that is meant for non-lifetime products on a lifetime price option', 'edd-advanced-discounts' ) );
                                return false;
                        }
                }
                
                // check price options
                if( $price_options = $discount->get_meta( 'price_options', true ) ){
                        // iterate through cart items
                        foreach( $cart_content as $_item ){
                                $download = new EDD_SL_Download( $_item['id'] );
                                if( is_array( $price_options ) 
                                        && $_item['options']['price_id'] 
                                        && ! in_array( $_item['options']['price_id'], $price_options ) ){
                                    
                                                edd_set_error( 'edd-discount-error', _x( 'This discount can only be used for specific price options.', 'error shown when attempting to use a discount that is meant specific price options', 'edd-advanced-discounts' ) );
                                                return false;
                                }
                        }

                }
            
        }
        
        // check price options
        
        return $return;
}