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

        register_rest_route('custom/v1', 'product-sales', array(
            'methods' => 'GET',
            'callback' => 'get_product_sale',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'categories', array(
            'methods' => 'GET',
            'callback' => 'custom_get_frontend_product_menus',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'all-categories', array(
            'methods' => 'GET',
            'callback' => 'custom_get_product_categories',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'attributes', array(
            'methods' => 'GET',
            'callback' => 'wl_get_all_attribute',
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

        register_rest_route('custom/v1', 'products-price', array(
            'methods' => 'GET',
            'callback' => 'wl_get_product_by_price',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'products-details/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => 'get_product_by_id',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'product-variations/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => 'get_product_variation_by_id',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', 'customers', array(
            'methods' => 'GET',
            'callback' => 'wl_get_all_customers',
            'permission_callback' => '__return_true'
        ));

        register_rest_route('custom/v1', '/coupon/check', array(
            'methods' => 'POST',
            'callback' => 'custom_coupon_check',
            'permission_callback' => '__return_true'
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
            // 'args' => array(
            //     'id' => array(
            //         'validate_callback' => function ($param, $request, $key) {
            //             return is_numeric($param);
            //         },
            //     ),
            // ),
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

        register_rest_route('product/v1', '/products/attributes', array(
            'methods' => 'GET',
            'callback' => 'custom_product_filter_by_attributes',
        ));

        register_rest_route('product/v1', '/products/filter', array(
            'methods' => 'GET',
            'callback' => 'custom_filter_products',
            'permission_callback' => '__return_true', // Set permission callback as needed
        ));
    }
);

//  get All product
function wl_get_all_products($data)
{
    $limit = isset($data['limit']) ? $data['limit'] : 10;
    $order = isset($data['order']) ? $data['order'] : 'desc';

    $args = array(
        'limit' => $limit,
        'orderby' => 'date',
        'order' => $order
    );

    // Perform Query
    $query = new WC_Product_Query($args);
    $data = [];
    // Collect Product Object
    $products = $query->get_products();
    // Loop through products
    if (!empty($products)) {

        foreach ($products as $product) {
            $data[] = single_product_data($product);
        }
        return $data;
    }
}

// product sale
function get_product_sale()
{
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_sale_price',
                'value' => 0,
                'compare' => '>',
                'type' => 'NUMERIC',
            ),
        ),
    );

    // Query WooCommerce products
    $query = new WP_Query($args);

    $sale_products = array();

    // Loop through the products
    while ($query->have_posts()) {
        $query->the_post();
        $product = wc_get_product(get_the_ID());

        $sale_products[] = single_product_data($product);
    }

    wp_reset_postdata();

    return rest_ensure_response($sale_products);
}

//  get all categories 
function custom_get_frontend_product_menus()
{
    $product_categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false, // Set to true if you want to exclude empty categories
        'parent' => 0, // Get top-level categories
    ));

    $menus = array();

    foreach ($product_categories as $category) {
        $menus[] = custom_get_product_menu_hierarchy($category);
    }

    return rest_ensure_response($menus);
}

function custom_get_product_menu_hierarchy($category)
{
    $menu_item = array(
        'id' => $category->term_id,
        'name' => $category->name,
        'slug' => $category->slug,
        'link' => get_term_link($category), // Get the category archive link
        'children' => array(),
    );

    // Get the children of the current category
    $children = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false, // Set to true if you want to exclude empty categories
        'parent' => $category->term_id,
    ));

    foreach ($children as $child) {
        $menu_item['children'][] = custom_get_product_menu_hierarchy($child);
    }

    return $menu_item;
}

// get all category product
function custom_get_product_categories()
{
    $product_categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false, // Set to true if you want to exclude empty categories
    ));

    $categories = array();

    foreach ($product_categories as $category) {
        $categories[] = array(
            'id' => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent' => $category->parent,
            'link' => get_term_link($category), // Get the category archive link
            // Add more category details as needed
        );
    }

    return rest_ensure_response($categories);
}

//  get all attribute 
function wl_get_all_attribute()
{
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    $taxonomy_terms = array();

    if ($attribute_taxonomies) {
        foreach ($attribute_taxonomies as $tax) {
            if (taxonomy_exists(wc_attribute_taxonomy_name($tax->attribute_name))) {
                $taxonomy_terms[$tax->attribute_name] = get_terms(wc_attribute_taxonomy_name($tax->attribute_name), 'orderby=name&hide_empty=0');
            }
        }
    }

    return $taxonomy_terms;
}

// get product by categories
function wl_get_categories_products($req)
{
    $slug = $req['slug'];
    $products = wc_get_products(array(
        'category' => array($slug),
    ));
    if (count($products) == 0) {
        return wpc_error_404();
    }
    $product = get_all_data_in_format_wc_products($products);

    $data = [];
    foreach ($product as $items) {
        $product_variant = get_product_variation_by_id($items);
        $data[] = [
            "product_variant" => $product_variant,
            "item_product" => $items,
        ];
    }
    return $data;
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

// get product within_ price
function wl_get_product_by_price($req)
{
    $min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
    $max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : PHP_FLOAT_MAX;

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_price',
                'value'   => array($min_price, $max_price),
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN',
            ),
        ),
    );

    $query = new WP_Query($args);
    $respone = [];

    $products = array();
    while ($query->have_posts()) {
        $query->the_post();
        $products[] = wc_get_product(get_the_ID())->get_data();
    }
    foreach ($products as $item) {
        $product_variant = get_product_variation_by_id($item);
        $respone[] = [
            'product_variant_data' => $product_variant,
            'data_product' => $item
        ];
    }

    wp_reset_postdata();

    wp_send_json($respone);
}

// get product by id
function get_product_by_id($req)
{
    // Get product ID from the request
    $product_id = $req['id'];
    $product = wc_get_product($product_id);
    return single_product_data($product);
}

// product vatiation
function get_product_variation_by_id($data)
{
    // Get product ID from the request
    $product_id = $data['id'];
    // return $product_id;
    $product = wc_get_product($product_id);
    $current_products = $product->get_children();
    $data = [];
    foreach ($current_products as $variation_id) {
        $products = wc_get_product($variation_id);
        $data[] = $products->get_data();
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
            $show = wc_get_product($item);
            $data[] = single_product_data($show);
        }
        return $data;
    }
}

// get all customer
function wl_get_all_customers()
{
    $args = array(
        'role'    => 'customer',
        'orderby' => 'user_nicename',
        'order'   => 'ASC'
    );

    $customer = new WC_Customer($args);

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


    return $customer;
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
    $name = [];
    foreach ($products as $product) {
        $category_name = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));

        $data[$i]['id'] = $product->get_id();
        $data[$i]['name'] = $product->get_title();
        $data[$i]['slug'] = $product->get_slug();
        $data[$i]['sku'] = $product->get_sku();
        $data[$i]['category'] = $category_name;
        $data[$i]['price'] =  intval($product->get_price());
        $data[$i]['sale_price'] = intval($product->get_sale_price());
        $data[$i]['featuredImage'] = wp_get_attachment_image_url($product->get_image_id(), 'full');
        $data[$i]['ratings'] = intval($product->get_average_rating());
        $data[$i]['seller'] = get_userdata(get_post_field("post_author", $product->get_id()))->user_nicename;
        $i++;
    }

    return $data;
}

// format data product
function single_product_data($product)
{
    $data = [];

    $i = 0;
    $g = 0;
    $img = 0;
    $data['id'] = $product->get_id();
    $data['name'] = $product->get_title();
    $data['slug'] = $product->get_slug();
    $data['sku'] = $product->get_sku();
    $data['type'] = $product->get_type();
    $data['regular_price'] = $product->get_regular_price();
    $data['price'] = $product->get_price();
    $data['stock_quantity'] = $product->get_stock_quantity();
    $data['stockstock_status_quantity'] = $product->get_stock_status();
    $data['salePrice'] = $product->get_sale_price();
    $data['featuredImage'] = wp_get_attachment_image_url($product->get_image_id(), 'full');
    $data['ratings'] = $product->get_average_rating();
    $data['shortDescription'] = $product->get_short_description();
    $data['description'] = $product->get_description();
    $data['categories'] = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
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

// check coupond
function custom_coupon_check($data)
{
    $coupon_code = $data['coupon_code'];

    // Check if the coupon code exists
    $coupon = new WC_Coupon($coupon_code);

    if ($coupon->get_id()) {
        $response = array(
            'status' => 'success',
            'message' => 'Coupon is valid.',
        );
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'Invalid coupon code.',
        );
    }

    return rest_ensure_response($response);
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

// create order
function custom_create_order($request)
{
    $parameters = $request->get_json_params();
    $customer_id = isset($parameters['customer_id']) ? absint($parameters['customer_id']) : get_current_user_id();
    $paymen_cod = $parameters['payment_method'] ? $parameters['payment_method'] : " ";
    $Payment_title = $parameters['payment_method_title'] ? $parameters['payment_method_title'] : " ";
    $product_variation_data = isset($parameters['products']) ? $parameters['products'] : array();

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

    if (empty($customer_id) || empty($product_variation_data)) {
        return array(
            'success' => false,
            'message' => 'Customer data and product variation data are required.',
        );
    }

    if (is_wp_error($customer_id)) {
        return array(
            'success' => false,
            'message' => 'Error creating the customer.',
        );
    }

    // Your order creation logic here
    $order = wc_create_order(array('customer_id' => $customer_id));
    if (is_wp_error($order)) {
        return array(
            'success' => false,
            'message' => 'Error creating the order.',
        );
    }
    $order_id = $order->get_id();
    $order->set_payment_method($paymen_cod);
    $order->set_status('pending');
    $order->set_payment_method_title($Payment_title);
    $order->set_billing_address($billing_address);
    $order->set_shipping_address($shipping_address);

    foreach ($product_variation_data as $variation) {
        $product_id    = $variation['product_id'];
        $quantity      = $variation['quantity'];
        $variation_id  = $variation['variation_id'];

        if ($variation_id) {
            $product_variation = new WC_Product_Variation($variation_id);
            if ($product_variation->get_stock_quantity() < $quantity) {
                return array(
                    'success' => false,
                    'message' => 'Quantity in stock is not enough.',
                );
            };
            $args = array();
            foreach ($product_variation->get_variation_attributes() as $attribute => $attribute_value) {
                $args['variation'][$attribute] = $attribute_value;
            }
            $order->add_product($product_variation, $quantity, $args);
        } else {
            $order->add_product(
                wc_get_product($product_id),
                $quantity
            );
        }
    }
    $order->calculate_totals();
    // $order->save();

    // Get the WC_Email_New_Order object
    $email_new_order = WC()->mailer()->get_emails()['WC_Email_New_Order'];

    // Sending the new Order email notification for an $order_id (order ID)
    $email_new_order->trigger($order_id);
    wp_schedule_single_event(time(), 'custom_send_order_email_event', array($order_id));

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
    $data = [];
    foreach ($orders as $order) {
        foreach ($order->get_items() as  $item_key => $item_values) {
            $item_data = $item_values->get_data();
            $data[] = [
                'product_name' => $item_data['name'],
                'quantity' => $item_data['quantity'],
                'line_total' => $item_data['total']
            ];
        }
        $formatted_orders[] = array(
            'order_id'    => $order->get_id(),
            'order_total' => $order->get_total(),
            'order_date'  => $order->get_date_created(),
            'product_name' => $data,
            'order_status' => $order->get_status(),
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

// update customer
function update_customer_info(WP_REST_Request $request)
{

    $parameters = $request->get_json_params();

    $account_first_name = $parameters['account_first_name'] ? $parameters['account_first_name'] : " ";
    $account_last_name = $parameters['account_last_name'] ? $parameters['account_last_name'] : " ";
    $account_display_name = $parameters['account_display_name'] ? $parameters['account_display_name'] : " ";

    $pass_cur             = !empty($_POST['password_current']) ? $_POST['password_current'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    $pass1                = !empty($_POST['password_1']) ? $_POST['password_1'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    $pass2                = !empty($_POST['password_2']) ? $_POST['password_2'] : ''; // p

    $customer_id = $request['id'];

    $user_id = get_current_user_id();

    if ($customer_id != $user_id) {
        return;
    }
    // Current user data.
    $current_user       = get_user_by('id', $user_id);
    $current_first_name = $current_user->first_name;
    $current_last_name  = $current_user->last_name;
    $current_email      = $current_user->user_email;
    $current_display_name = $current_user->display_name;

    // New user data.
    $user               = new stdClass();
    $user->ID           = $user_id;
    $user->first_name   = $account_first_name;
    $user->last_name    = $account_last_name;
    $user->display_name = $account_display_name;
    // Prevent display name to be changed to email.
    if (is_email($account_display_name)) {
        wc_add_notice(__('Display name cannot be changed to email address due to privacy concern.', 'woocommerce'), 'error');
    }


    if (!empty($pass_cur) && empty($pass1) && empty($pass2)) {
        wc_add_notice(__('Please fill out all password fields.', 'woocommerce'), 'error');
        $save_pass = false;
    } elseif (!empty($pass1) && empty($pass_cur)) {
        wc_add_notice(__('Please enter your current password.', 'woocommerce'), 'error');
        $save_pass = false;
    } elseif (!empty($pass1) && empty($pass2)) {
        wc_add_notice(__('Please re-enter your password.', 'woocommerce'), 'error');
        $save_pass = false;
    } elseif ((!empty($pass1) || !empty($pass2)) && $pass1 !== $pass2) {
        wc_add_notice(__('New passwords do not match.', 'woocommerce'), 'error');
        $save_pass = false;
    } elseif (!empty($pass1) && !wp_check_password($pass_cur, $current_user->user_pass, $current_user->ID)) {
        wc_add_notice(__('Your current password is incorrect.', 'woocommerce'), 'error');
        $save_pass = false;
    }

    if ($pass1 && $save_pass) {
        $user->user_pass = $pass1;
    }
    $customer = new WC_Customer($user_id);
    if ($account_first_name != $current_first_name) {
        $customer->set_first_name($account_first_name);
    }
    if ($account_last_name != $current_last_name) {
        $customer->set_last_name($account_last_name);
    }
    if ($account_display_name != $current_display_name) {
        $customer->set_first_name($account_display_name);
    }

    $customer->save();

    return $customer;
    // Update customer information using WooCommerce functions or custom code
    // update_user_meta($customer_id, 'first_name', $account_first_name);
    // update_user_meta($customer_id, 'last_name', $account_last_name);
    // update_user_meta($customer_id, 'account_display_name', $account_display_name);

    // Return a response
    return new WP_REST_Response('Customer information updated successfully', 200);
}

// array of attribute terms
function custom_product_filter_by_attributes($data)
{
    $attributes = $data->get_params();
    if (!$attributes) {
        return wl_get_all_products($data);
    }
    $tax_query = [];
    foreach ($attributes as $attribute => $terms) {
        $tax_query[] = array(
            'taxonomy' => 'pa_' . $attribute, // Adjust the taxonomy based on your attribute
            'field' => 'slug',
            'terms' => $terms,
        );
    }
    // Query WooCommerce products with the specified attribute terms
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => $tax_query,
    );

    $products = get_posts($args);

    $formatted_products = array();

    foreach ($products as $product) {
        // $formatted_products[] = wc_get_product($product->ID)->get_data();
        $show = wc_get_product($product->ID);
        $formatted_products[] =  single_product_data($show);
    }

    return rest_ensure_response($formatted_products);
}

// array of attribute terms and a price range in the query parameters
// wp-json/custom/v1/products/filter?attributes[color]=red,blue&attributes[size]=small,medium&min_price=10&max_price=50
function custom_filter_products($data)
{
    $attributes = isset($data['attributes']) ? $data['attributes'] : array();
    $min_price = isset($data['min_price']) ? floatval($data['min_price']) : 0;
    $max_price = isset($data['max_price']) ? floatval($data['max_price']) : PHP_FLOAT_MAX;

    $tax_query = array();

    foreach ($attributes as $attribute => $terms) {
        $tax_query[] = array(
            'taxonomy' => 'pa_' . $attribute, // Adjust the taxonomy based on your attribute
            'field' => 'slug',
            'terms' => $terms,
        );
    }

    // Query WooCommerce products with the specified attribute terms and price range
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => $tax_query,
        'meta_query' => array(
            array(
                'key' => '_price',
                'value' => array($min_price, $max_price),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN',
            ),
        ),
    );

    $products = get_posts($args);

    $formatted_products = array();

    foreach ($products as $product) {
        $formatted_products[] = wc_get_product($product->ID)->get_data();
    }

    return rest_ensure_response($formatted_products);
}
