<?php

class My_Plugin_Shortcodes
{

    public function __construct()
    {
        add_shortcode('daniel_ld_all_courses', [$this, 'Daniel_learndash_all_courses_shortcode']);
        add_shortcode('daniel_ld_enrolled_courses', [$this, 'Daniel_learndash_enrolled_courses_shortcode']);
        add_shortcode('daniel_ld_custom_course_button', [$this, 'Daniel_learndash_custom_course_button_shortcode']);
        add_shortcode('daniel_ld_get_the_price_or_no_return_nothing', [$this, 'Daniel_learndash_get_the_price_or_no_return_nothing']);
    }

    public function Daniel_learndash_all_courses_shortcode()
    {
        $args = array(
            'post_type' => 'sfwd-courses',
            'posts_per_page' => -1
        );
        $courses = get_posts($args);

        $html = '<div class="learndash-all-courses">';

        foreach ($courses as $course) {
            $course_id = $course->ID;
            $course_title = get_the_title($course_id);
            $course_permalink = get_permalink($course_id);
            $featured_image = get_the_post_thumbnail_url($course_id, 'large');

            $html .= '<article class="course-item">';
            $html .= '<a href="' . esc_url($course_permalink) . '" class="course-item-link">';
            $html .= '<div class="course-image">';
            $html .= '<img src="' . esc_url($featured_image) . '" alt="' . esc_attr($course_title) . '">';
            $html .= '</div>';
            $html .= '<div class="info-container">';
            $html .= '<h2>' . esc_html($course_title) . '</h2>';
            $html .= '<div class="view-link">'; // Nuevo div para contener el enlace
            $html .= '<span>Ver →</span>'; // Span que contiene el texto "Ver"
            $html .= '</div>'; // Cierre del div view-link
            $html .= '</div>';
            $html .= '</a>';
            $html .= '</article>';
        }
        $html .= '</div>';

        return $html;
    }

    public function Daniel_learndash_enrolled_courses_shortcode()
    {
        $user_id = get_current_user_id(); // Obtener el ID del usuario actual

        // Obtener los cursos en los que el usuario está inscrito
        $enrolled_courses = learndash_user_get_enrolled_courses($user_id);

        $html = '<div class="learndash-enrolled-courses">';

        // Verificar si el usuario está inscrito en algún curso
        if (!empty($enrolled_courses)) {
            foreach ($enrolled_courses as $course_id) {
                $course_title = get_the_title($course_id);
                $course_permalink = get_permalink($course_id);
                $featured_image = get_the_post_thumbnail_url($course_id, 'large');

                $html .= '<article class="course-item">';
                $html .= '<a href="' . esc_url($course_permalink) . '" class="course-item-link">';
                $html .= '<div class="course-image">';
                $html .= '<img src="' . esc_url($featured_image) . '" alt="' . esc_attr($course_title) . '">';
                $html .= '</div>';
                $html .= '<div class="course-title"><h2>' . esc_html($course_title) . '</h2></div>';
                $html .= '</a>';
                $html .= '</article>';
            }
        } else {
            $html .= '<p class="alert-daniel">No estás inscrito en ningún curso actualmente.</p>';
        }

        $html .= '</div>';

        return $html;
    }

    public function Daniel_learndash_custom_course_button_shortcode($atts)
    {
        // Obtener el ID del curso
        $course_id = get_the_ID();

        // Obtener el usuario actual
        $user_id = get_current_user_id();

        // Verificar si el usuario está inscrito en el curso
        $is_enrolled = sfwd_lms_has_access($course_id, $user_id);

        // Obtener los metadatos del curso y deserializarlos
        $course_meta = get_post_meta($course_id, '_sfwd-courses', true);
        $course_meta = maybe_unserialize($course_meta);

        // Obtener el precio del curso y la URL de pago
        $course_price = isset($course_meta['sfwd-courses_course_price']) ? $course_meta['sfwd-courses_course_price'] : '';
        $payment_url = isset($course_meta['sfwd-courses_custom_button_url']) ? $course_meta['sfwd-courses_custom_button_url'] : '';

        if ($is_enrolled) {
            // Obtener todas las lecciones del curso
            $course_steps = learndash_course_get_steps_by_type($course_id, 'sfwd-lessons');

            // Encontrar la próxima lección no completada
            $next_lesson_id = null;
            foreach ($course_steps as $lesson_id) {
                $completed = learndash_is_lesson_complete($user_id, $lesson_id);
                if (!$completed) {
                    $next_lesson_id = $lesson_id;
                    break;
                }
            }

            if ($next_lesson_id) {
                $button_text = 'Continuar';
                $button_url = get_permalink($next_lesson_id);
            } else {
                // Si todas las lecciones están completadas, redirige a la primera lección
                $button_text = 'Volver al inicio';
                $first_lesson_id = reset($course_steps);
                $button_url = get_permalink($first_lesson_id);
            }

            // Construir el botón
            $output = '<div class="daniel-custom-course-button">';
            $output .= '<a href="' . esc_url($button_url) . '" class="btn btn-primary">' . esc_html($button_text) . '</a>';
            $output .= '</div>';

            return $output;
        }


        $button_text = '¡Comprar hoy!';
        $button_url = $payment_url;

        $login_text = 'Inicia sesión';
        $login_url = get_permalink(get_option('woocommerce_myaccount_page_id'));
        // Construir el botón
        $output = '<div class="daniel-custom-course-button">';
        $output .= '<a href="' . esc_url($button_url) . '" class="btn btn-primary">' . esc_html($button_text) . '</a>';
        $output .= '<div class="daniel-custom-course-button-login">';
        $output .= '<p>¿Ya compraste este curso?</p>';
        $output .= '<a href="' . esc_url($login_url) . '" class="btn btn-secondary">' . esc_html($login_text) . '</a>';
        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    public function Daniel_learndash_get_the_price_or_no_return_nothing($atts)
    {
        // Obtener el ID del curso
        $course_id = get_the_ID();

        // Obtener el usuario actual
        $user_id = get_current_user_id();

        // Verificar si el usuario está inscrito en el curso
        $is_enrolled = sfwd_lms_has_access($course_id, $user_id);

        // Obtener los metadatos del curso y deserializarlos
        $course_meta = get_post_meta($course_id, '_sfwd-courses', true);
        $course_meta = maybe_unserialize($course_meta);

        // Obtener el precio del curso y la URL de pago
        $course_price = isset($course_meta['sfwd-courses_course_price']) ? $course_meta['sfwd-courses_course_price'] : '';

        if ($is_enrolled) {
            $button_text = '';
        } else {
            $button_text = '<p class="price_text_shortcode">' . $course_price . '</p>';
        }

        // Construir el botón
        $output = '<div class="daniel-custom-course-price">';
        $output .= $button_text;
        $output .= '</div>';

        return $output;
    }
}
