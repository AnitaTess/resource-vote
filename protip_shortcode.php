<?php
if (! defined('ABSPATH')) {
    exit;
}

function protip_votes_shortcode($atts)
{
    $atts = shortcode_atts(
        array(
            'limit' => 12,
            'topic' => '',
        ),
        $atts,
        'protip_votes'
    );

    //limited the number of posts so someone cannot accidentally query hundreds of posts
    $limit = absint($atts['limit']);
    if ($limit < 1) {
        $limit = 1;
    }

    if ($limit > 18) {
        $limit = 18;
    }
    //sanitised the shortcode topic because it comes from user-editable shortcode input
    $topic = sanitize_title($atts['topic']);

    //Base query arguments to get pro-tips loop
    $args = array(
        'post_type'      => 'protip',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        //use no_found_rows because the shortcode does not need pagination, 
        //so WordPress does not need to calculate the total number of matching posts
        'no_found_rows'  => true,
    );

    //If topic is provided, add a taxonomy query
    if (! empty($topic)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'protip_topic',
                'field'    => 'slug',
                'terms'    => $topic,
            ),
        );
    }
    //WP_Query
    $protip_query = new WP_Query($args);

    //because this is inside a shortcode function, wrappping the output with ob_start(); and ob_get_clean() 
    //to return the output as a string instead of echoing it directly
    ob_start();
    if ($protip_query->have_posts()) : ?>
        <!-- pagination here -->
         <button type="button" class="ptv-refresh">
    <?php esc_html_e( 'Check latest vote counts', 'protip-votes' ); ?>
</button>
        <div class="ptv-grid">
            <!-- the loop -->
            <?php
            $count = 0;
            while ($protip_query->have_posts()) :
                $protip_query->the_post();
                $title_id = 'ptv-card-title-' . get_the_ID();
                $message_id = 'ptv-card-message-' . get_the_ID();

                $count++;
                $card_classes = 'ptv-card';

                if (1 === $count) {
                    $card_classes .= ' ptv-card--featured';
                }

            ?>
                <article class="<?php echo esc_attr($card_classes); ?>" aria-labelledby="<?php echo esc_attr($title_id); ?>">
                    <h3 id="<?php echo esc_attr($title_id); ?>" class="ptv-card__title">
                        <?php echo esc_html(get_the_title()); ?>
                    </h3>
                    <div class="ptv-card__excerpt">
                        <?php echo wp_kses_post(get_the_excerpt()); ?>
                    </div>
                     <p id="<?php echo esc_attr( $message_id ); ?>" class="ptv-card__message" aria-live="polite">
                <?php echo esc_html( 'Votes: ' . protip_get_vote_count( get_the_ID() ) ); ?>
            </p>
                    <div class="ptv-card__button-container">
                        <button
                            type="button"
                            class="ptv-card__button"
                            data-protip-id="<?php echo esc_attr(get_the_ID()); ?>"
                            aria-describedby="<?php echo esc_attr($message_id); ?>">
                            
                            <?php esc_html_e('Vote for this tip', 'protip-votes'); ?>
                        </button>
                    </div>
                </article>
            <?php endwhile; ?>
            <!-- end of the loop -->
        </div>

    <?php else : ?>
        <p class="ptv-empty"><?php esc_html_e('Sorry, no pro-tips found.', 'protip-votes'); ?></p>
    <?php endif; ?>
<?php
    //use wp_reset_postdata() after a custom query so the global post object goes back to the main query
    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode('protip_votes', 'protip_votes_shortcode');
