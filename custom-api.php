<?php

/**
 * Plugin Name: Custom API
 * Plugin URI: https://github.com/dinhduan2710/wp-api
 * Description: Custom API endpoint
 * Version: 1.0.0
 * Author: Duan Dinh
 */

add_action(
    'rest_api_init',
    function () {
        register_rest_route('custom/v1', 'products', array(
            'methods' => 'GET',
            'callback' => 'wl_get_all_products',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'categories', array(
            'methods' => 'GET',
            'callback' => 'wl_get_all_categories',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'categories/(?P<slug>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => 'wl_get_categories_products',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'products/(?P<slug>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => 'wl_get_product',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'products-price/(?P<maxprice>[a-zA-Z0-9-]+)/(?P<minprice>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => 'wl_get_product_by_price',
            // 'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'products/(?P<id>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => 'wl_get_product_by_id',
            // 'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'customers', array(
            'methods' => 'GET',
            'callback' => 'wl_get_all_customers',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'check-coupon', array(
            'methods' => 'GET',
            'callback' => 'wpc_check_coupon_valid',
            // 'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'available-payment-gateways', array(
            'methods' => 'GET',
            'callback' => 'wpc_get_all_available_payment_gateways',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', '/related-products/(?P<product_id>\d+)', array(
            'methods' => 'GET',
            'callback' => 'wpc_get_related_products',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', '/flash-sale-products', array(
            'methods' => 'GET',
            'callback' => 'get_custom_flash_sale_products',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', '/add-wishlist', array(
            'methods' => 'POST',
            'callback' => 'custom_add_to_wishlist',
            // 'permission_callback' => function () {
            //     return is_user_logged_in();
            // },
        ));

        register_rest_route('custom/v1', '/get-wishlist', array(
            'methods' => 'GET',
            'callback' => 'custom_get_wishlist',
            // 'permission_callback' => function () {
            //     return is_user_logged_in();
            // },
        ));

        register_rest_route('custom/v1', '/wishlist', array(
            'methods' => array('DELETE'),
            'callback' => 'custom_delete_wishlist',
            // 'permission_callback' => function () {
            //     return is_user_logged_in();
            // },
        ));

        register_rest_route('custom/v1', '/create-order', array(
            'methods'  => 'POST',
            'callback' => 'custom_create_order',
            // 'permission_callback' => function () {
            //     return current_user_can('edit_posts');
            // },
        ));

        register_rest_route('custom/v1', '/get-order', array(
            'methods'  => 'GET',
            'callback' => 'custom_get_orders',
            // 'permission_callback' => function () {
            //     return current_user_can('edit_posts');
            // },
        ));

        register_rest_route('custom/v1', '/order/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => 'custom_get_order_by_id',
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        register_rest_route('custom/v1', '/order/(?P<id>\d+)/products', array(
            'methods' => 'GET',
            'callback' => 'custom_get_product_items_by_order_id',
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));
    }
);

//  get All product
function wl_get_all_products()
{
    $products = wc_get_products(array(
        "status" => "published"
    ));
    return get_all_data_in_format_wc_products($products);
}

//  get all categories 
function wl_get_all_categories()
{
    $data = [];
    $i = 0;
    $orderby = 'name';

    $order = 'asc';
    $hide_empty = false;
    $args = array(
        'orderby'    => $orderby,
        'order'      => $order,
        'hide_empty' => $hide_empty,

    );

    $product_categories = get_terms('product_cat', $args);
    foreach ($product_categories as $catg) {
        $thumbnail_id = get_term_meta($catg->term_id, 'thumbnail_id', true);
        $data[$i]['id'] = $catg->term_id;
        $data[$i]['name'] = $catg->name;
        $data[$i]['slug'] = $catg->slug;
        $data[$i]['totalProducts'] = $catg->count;
        $data[$i]['featuredImage'] = wp_get_attachment_url($thumbnail_id);
        $i++;
    }
    return $data;
}

// get product by category
function wl_get_categories_products($req)
{
    $slug = $req['slug'];
    $products = wc_get_products(array(
        'category' => array($slug),
    ));
    if (count($products) == 0) {
        return wpc_error_404();
    }
    return get_all_data_in_format_wc_products($products);
}

// get product by slug
function wl_get_product($req)
{
    $slug = $req['slug'];
    $productId = get_page_by_path($slug, OBJECT, 'product');
    if (!empty($productId)) {
        $product = wc_get_product($productId);
        return single_product_data($product);
    }
    return wpc_error_404();
}

// get product by slug
function wl_get_product_by_price($req)
{
    return $req;
    $slug = $req['slug'];
    $productId = get_page_by_path($slug, OBJECT, 'product');
    if (!empty($productId)) {
        $product = wc_get_product($productId);
        return single_product_data($product);
    }
    return wpc_error_404();
}

// get product by id
function wl_get_product_by_id($req)
{
    $id = $req['id'];
    return $id;
    $productId = get_page_by_path($id, OBJECT, 'product');
    if (!empty($productId)) {
        $product = wc_get_product($productId);
        return single_product_data($product);
    }
    return wpc_error_404();
}

// get all customer
function wl_get_all_customers()
{
    $args = array(
        'role'    => 'customer',
        'orderby' => 'user_nicename',
        'order'   => 'ASC'
    );

    $customer = new WC_Customer(15);

    $username     = $customer->get_username(); // Get username
    $user_email   = $customer->get_email(); // Get account email
    $first_name   = $customer->get_first_name();
    $last_name    = $customer->get_last_name();
    $display_name = $customer->get_display_name();

    // Customer billing information details (from account)
    $billing_first_name = $customer->get_billing_first_name();
    $billing_last_name  = $customer->get_billing_last_name();
    $billing_company    = $customer->get_billing_company();
    $billing_address_1  = $customer->get_billing_address_1();
    $billing_address_2  = $customer->get_billing_address_2();
    $billing_city       = $customer->get_billing_city();
    $billing_state      = $customer->get_billing_state();
    $billing_postcode   = $customer->get_billing_postcode();
    $billing_country    = $customer->get_billing_country();

    // Customer shipping information details (from account)
    $shipping_first_name = $customer->get_shipping_first_name();
    $shipping_last_name  = $customer->get_shipping_last_name();
    $shipping_company    = $customer->get_shipping_company();
    $shipping_address_1  = $customer->get_shipping_address_1();
    $shipping_address_2  = $customer->get_shipping_address_2();
    $shipping_city       = $customer->get_shipping_city();
    $shipping_state      = $customer->get_shipping_state();
    $shipping_postcode   = $customer->get_shipping_postcode();
    $shipping_country    = $customer->get_shipping_country();


    return $shipping_country;
}

// get mess err
function wpc_error_404()
{
    $error = [];
    $error['msg'] = "Error Not Found";
    $error['status'] = 404;
    return $error;
}

function get_all_data_in_format_wc_products($products)
{
    $data = [];
    $i = 0;
    foreach ($products as $product) {

        $data[$i]['id'] = $product->get_id();
        $data[$i]['name'] = $product->get_title();
        $data[$i]['slug'] = $product->get_slug();
        $data[$i]['price'] =  intval($product->get_price());
        $data[$i]['sale_price'] = intval($product->get_sale_price());
        $data[$i]['featuredImage'] = wp_get_attachment_image_url($product->get_image_id(), 'full');
        $data[$i]['ratings'] = intval($product->get_average_rating());
        $data[$i]['seller'] = get_userdata(get_post_field("post_author", $product->get_id()))->user_nicename;

        $i++;
    }

    return $data;
}

function single_product_data($product)
{
    $data = [];

    $i = 0;
    $g = 0;
    $img = 0;
    $data['id'] = $product->get_id();
    $data['name'] = $product->get_title();
    $data['slug'] = $product->get_slug();
    $data['type'] = $product->get_type();
    $data['price'] = $product->get_price();
    $data['salePrice'] = $product->get_sale_price();
    $data['featuredImage'] = wp_get_attachment_image_url($product->get_image_id(), 'full');
    $data['ratings'] = $product->get_average_rating();
    $data['shortDescription'] = $product->get_short_description();
    $data['description'] = $product->get_description();
    $data['categories'] = get_the_terms($product->get_id(), 'product_cat');
    $data['seller'] = get_userdata(get_post_field("post_author", $product->get_id()))->user_nicename;
    $data['isDownloadable'] = $product->is_downloadable();
    $data['crossSellCount'] = count($product->get_cross_sell_ids());
    $attachment_ids = $product->get_gallery_image_ids();
    $data['gallImgCOunt'] = count($attachment_ids);


    foreach ($attachment_ids as $attachment_id) {

        $data["galleryImgs"][$img] = wp_get_attachment_url($attachment_id);

        $img++;
    }
    if (!empty($product->get_cross_sell_ids())) {
        foreach ($product->get_cross_sell_ids() as $crsId) {
            $data['crossSellProducts'][$g] = wpc_get_related_products($crsId);
            $g++;
        }
    }
    if (!empty($product->get_upsells())) {

        foreach ($product->get_upsells() as $rpd) {
            $data['upsellProducts'][$i] = wpc_get_related_products($rpd);
            $i++;
        }
    }

    $attributes = $product->get_attributes();

    if ($attributes) {

        foreach ($attributes as $attribute) {

            $data['attributes'][$attribute['name']] =  $product->get_attribute($attribute['name']);
        }
    }
    if ($product->is_type('variable')) {
        $data['variations'] = $product->get_available_variations();
    }
    if ($product->has_dimensions()) {
        $data['dimension']['width'] = $product->get_width();
        $data['dimension']['length'] = $product->get_length();
        $data['dimension']['height'] = $product->get_height();
    }

    return $data;
}

//get product relates
function wpc_get_related_products($data)
{
    $productId =  $data['product_id'];
    $data = [];
    $product = wc_get_related_products($productId);
    if (!empty($product)) {
        foreach ($product as $item) {
            $show = $product = wc_get_product($item);
            $data[] = array(
                'product_id' => $item,
                'product_name' => $show->get_name(),
                'product_price' => $show->get_price(),
                'product_image' => get_the_post_thumbnail_url($item, 'thumbnail'),
            );
        }
    }
    return $data;
}

// check coupond
function wpc_check_coupon_valid($request)
{
    $coupons = [];

    $applied_coupons = WC()->cart->get_applied_coupons();
    return $applied_coupons;

    foreach ($applied_coupons as $coupon_code) {
        $coupon = new WC_Coupon($coupon_code);

        if ($coupon->exists() && $coupon->is_valid()) {
            $coupons[] = array(
                'coupon_code' => $coupon_code,
                'discount'    => $coupon->get_amount(),
                // Add more coupon details if needed
            );
        }
    }

    return $coupons;
}

//  get payment acaiable
function wpc_get_all_available_payment_gateways()
{
    $payemntsGateway = new WC_Payment_Gateways();
    $data = [];
    $i = 0;
    foreach ($payemntsGateway->get_available_payment_gateways() as $gateway) {
        if ($gateway->enabled == "yes") {
            $data[$i]['id'] = $gateway->id;
            $data[$i]['title'] = $gateway->title;
            $data[$i]['description'] = $gateway->description;

            $i++;
        }
    }

    return $data;
}

// get wish list
function custom_get_wishlist(WP_REST_Request $request)
{
    $user_id = get_current_user_id();

    // Retrieve the wishlist from user meta
    $wishlist = get_user_meta($user_id, 'wishlist', true);
    // If the wishlist doesn't exist, create an empty array
    if (!is_array($wishlist)) {
        $wishlist = array();
    }

    // Prepare an array to hold product details
    $wishlist_data = array();

    // Populate the array with product details
    foreach ($wishlist as $product_id) {
        $product = wc_get_product($product_id['product_id']);
        if ($product) {
            $wishlist_data[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                // Add more product details as needed
            );
        }
    }

    // Return the wishlist data
    return new WP_REST_Response($wishlist_data, 200);
}

// add wishlist
function custom_add_to_wishlist(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $product_id = $request->get_param('product_id');

    if (empty($product_id)) {
        return new WP_Error('invalid_product_id', 'Product ID is required.', array('status' => 400));
    }

    // Check if product exists
    $product = wc_get_product($product_id);
    if (!$product) {
        return new WP_Error('invalid_product', 'Invalid product ID.', array('status' => 400));
    }

    // Get user's wishlist
    $wishlist = get_user_meta($user_id, 'wishlist', true);
    $datetime_now      = new WC_DateTime(); // Get now datetime (from Woocommerce datetime object)
    $timestamp_now     = $datetime_now->getTimestamp();
    // Add the product to the wishlist
    // If the wishlist doesn't exist, create an empty array
    if (!is_array($wishlist)) {
        $wishlist = array();
    }

    $product_id_to_add = $product_id;  // Replace with the actual product ID

    // Check if the product is not already in the wishlist
    if (!in_array($product_id_to_add, $wishlist)) {
        $wishlist[] = [
            "product_id" => $product_id_to_add,
            "date" => $timestamp_now
        ];
    }

    // Update the user's wishlist meta field
    update_user_meta($user_id, 'wishlist', $wishlist);

    return $wishlist;
}

//delete wishlist
function custom_delete_wishlist(WP_REST_Request $request)
{
    $user_id = get_current_user_id();
    $product_id = $request->get_param('product_id');

    if (empty($product_id)) {
        return new WP_Error('invalid_product_id', 'Product ID is required.', array('status' => 400));
    }

    // Check if product exists
    $product = wc_get_product($product_id);
    if (!$product) {
        return new WP_Error('invalid_product', 'Invalid product ID.', array('status' => 400));
    }

    $wishlist = get_user_meta($user_id, 'wishlist', true);

    if (in_array($product_id, $wishlist)) {
        // Remove product from the wishlist
        $updated_wishlist = array_diff($wishlist, array($product_id));
        update_user_meta($user_id, 'wishlist', $updated_wishlist);

        return new WP_REST_Response('Product removed from wishlist.', 200);
    } else {
        return new WP_Error('product_not_in_wishlist', 'Product not found in wishlist.', array('status' => 404));
    }
}

// create order
function custom_create_order($request)
{
    $parameters = $request->get_json_params();
    $customer_id = isset($parameters['customer_id']) ? absint($parameters['customer_id']) : get_current_user_id();
    $paymen_cod = $parameters['payment_method'] ? $parameters['payment_method'] : " " ;
    $Payment_title = $parameters['payment_method_title'] ? $parameters['payment_method_title'] : " ";

    $billing_address = [
        'first_name' => $parameters['first_name'],
        'email'      => $parameters['email'],
        'address_1'  => $parameters['address_1'],
        'address_2'  => $parameters['address_2'],
        'city'       => $parameters['city'],
        'state'      => $parameters['state'],
        'postcode'   => $parameters['postcode'],
        'country'    => $parameters['country'],
    ];

    // Shipping information
    $shipping_address = [
        'first_name' => $parameters['first_name'],
        'address_1'  => $parameters['address_1'],
        'address_2'  => $parameters['address_2'],
        'city'       => $parameters['city'],
        'state'      => $parameters['state'],
        'postcode'   => $parameters['postcode'],
        'country'    => $parameters['country'],
    ];

    // Your order creation logic here
    $order = wc_create_order();

    $order->set_customer_id($customer_id);
    $order->set_payment_method($paymen_cod);
    $order->set_status('pending');
    $order->set_payment_method_title($Payment_title);
    $order->set_billing_address($billing_address);
    $order->set_shipping_address($shipping_address);

    foreach ($request['products'] as $product) {
        $order->add_product(wc_get_product($product['id']), $product['quantity']);
    }
    $order->calculate_totals();
    $order->save();

    $order_id = $order->get_id();

    // Get the WC_Email_New_Order object
    $email_new_order = WC()->mailer()->get_emails()['WC_Email_New_Order'];

    // Sending the new Order email notification for an $order_id (order ID)
    $email_new_order->trigger($order_id);
    // wp_schedule_single_event(time(), 'custom_send_order_email_event', array($order_id));

    return array(
        'success' => true,
        'message' => 'Order created successfully',
        'order_id' => $order_id,
        'item' => $order->get_data()
    );
}

// get order
function custom_get_orders($request)
{
    $customer_id = get_current_user_id();

    if (empty($customer_id)) {
        return new WP_Error('invalid_customer_id', 'Customer ID is required.', array('status' => 400));
    }

    $orders = wc_get_orders(array(
        'customer' => $customer_id,
    ));

    $formatted_orders = array();

    foreach ($orders as $order) {
        $formatted_orders[] = array(
            'order_id'    => $order->get_id(),
            'order_total' => $order->get_total(),
            'order_date'  => $order->get_date_created(),
            "order_status" => $order->get_status(),
            // Add more fields as needed
        );
    }

    return $formatted_orders;
}

// customer get order by id
function custom_get_order_by_id($data)
{
    $order_id = $data['id'];

    $user_id = get_current_user_id();
    // Get the order data
    $order = wc_get_order($order_id);

    // Check if the order exists
    if (!$order) {
        return new WP_Error('custom_order_not_found', __('Order not found'), array('status' => 404));
    }

    // Get customer information from the order
    $customer_id = $order->get_customer_id();
    if ($user_id == $customer_id) {
        $customer_data = get_userdata($customer_id);

        // Prepare order data along with customer information
        $order_data = array(
            'order' => $order->get_data(),
            'customer_info' => array(
                'customer_id' => $customer_id,
                'customer_username' => $customer_data->user_login,
                'customer_email' => $customer_data->user_email,
            ),
        );

        return rest_ensure_response($order_data);
    }
    return false;
}

// customer get item order by id
function custom_get_product_items_by_order_id($data)
{
    $order_id = $data['id'];

    // Get the order data
    $order = wc_get_order($order_id);
    // Check if the order exists
    if (!$order) {
        return new WP_Error('custom_order_not_found', __('Order not found'), array('status' => 404));
    }

    // Get customer information from the order
    $customer_id = $order->get_customer_id();
    $customer_data = get_userdata($customer_id);

    // Get product items from the order
    $product_items = array();

    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $product_items[] = array(
            'product_id' => $product->get_id(),
            'product_name' => $product->get_name(),
            'product_price' => $product->get_price(),
            'quantity' => $item->get_quantity(),
        );
    }

    // Prepare response data
    $response_data = array(
        'order_id' => $order_id,
        'customer_info' => array(
            'customer_id' => $customer_id,
            'customer_username' => $customer_data->user_login,
            'customer_email' => $customer_data->user_email,
        ),
        'product_items' => $product_items,
    );

    return rest_ensure_response($response_data);
}
