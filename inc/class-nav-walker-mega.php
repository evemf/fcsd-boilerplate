<?php
/**
 * Walker del menú principal con soporte de mega menús.
 *
 * Objetivo:
 * - Seguir usando el sistema nativo de Menús de WP (Apariencia > Menús)
 * - Añadir, automáticamente, los mega menús existentes cuando el item corresponde
 *   a “Qui som” o “Serveis”, sin que el editor tenga que añadir clases CSS.
 */
defined('ABSPATH') || exit;

class FCSD_Nav_Walker_Mega extends Walker_Nav_Menu {

    /**
     * Injecta la propietat has_children a $args (necessari per a Bootstrap)
     * i, en el cas dels mega-menús, evita renderitzar els seus fills natius.
     *
     * Això resol el problema on els subelements apareixien com a elements
     * de primer nivell (perquè s'havia desactivat start_lvl()).
     */
    public function display_element( $element, &$children_elements, $max_depth, $depth = 0, $args = null, &$output = '' ) {
        if ( ! $element ) {
            return;
        }

        $id_field     = $this->db_fields['id'];
        $element_id   = $element->$id_field;
        $has_children = ! empty( $children_elements[ $element_id ] );

        if ( is_array( $args ) && ! empty( $args[0] ) && is_object( $args[0] ) ) {
            $args[0]->has_children = $has_children;
        }

        // Si és un mega-menú (només a nivell superior), no volem imprimir els fills
        // natius (els mostrarem amb el markup de mega-menú propi del tema).
        $classes         = empty( $element->classes ) ? array() : (array) $element->classes;
        $classes         = array_filter( $classes );
        $is_mega_parent  = ( 0 === (int) $depth ) && (
            in_array( 'fcsd-mega-quisom', $classes, true ) ||
            in_array( 'fcsd-mega-serveis', $classes, true )
        );

        if ( $is_mega_parent && $has_children ) {
            unset( $children_elements[ $element_id ] );
            if ( is_array( $args ) && ! empty( $args[0] ) && is_object( $args[0] ) ) {
                $args[0]->has_children = false;
            }
        }

        parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
    }

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        // Submenú compatible amb Bootstrap.
        $indent = str_repeat( "\t", (int) $depth );
        $output .= "\n$indent<ul class=\"dropdown-menu\">\n";
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", (int) $depth );
        $output .= "$indent</ul>\n";
    }

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        if ( ! ($item instanceof WP_Post) ) return;

        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes = array_filter($classes);

        $is_mega_quisom   = in_array('fcsd-mega-quisom', $classes, true);
        $is_mega_serveis  = in_array('fcsd-mega-serveis', $classes, true);
        $has_mega = ($is_mega_quisom || $is_mega_serveis);

        $has_children = ( isset( $args->has_children ) && $args->has_children );

        // Classes per a <li> segons nivell.
        $li_classes = ( 0 === (int) $depth ) ? array( 'nav-item' ) : array();

        if ( 0 === (int) $depth ) {
            if ( $has_mega || $has_children ) {
                $li_classes[] = 'dropdown';
                if ( $has_mega ) {
                    $li_classes[] = 'position-static';
                }
            }
        } else {
            // Subnivells dins el dropdown.
            if ( $has_children ) {
                $li_classes[] = 'dropend';
            }
        }

        $output .= '<li' . ( ! empty( $li_classes ) ? ' class="' . esc_attr( implode( ' ', $li_classes ) ) . '"' : '' ) . '>';

        $atts = array();
        $atts['href']  = ! empty( $item->url ) ? $item->url : '';
        $atts['class'] = ( 0 === (int) $depth ) ? 'nav-link' : 'dropdown-item';

        // Dropdown natiu (subpàgines) o mega-menú (markup propi del tema).
        if ( $has_mega || $has_children ) {
            // Els mega-menús només a nivell superior.
            if ( $has_mega && 0 === (int) $depth ) {
                $atts['class'] .= ' dropdown-toggle';
                $atts['role'] = 'button';
                $atts['aria-expanded'] = 'false';
                $atts['data-mega'] = $is_mega_quisom ? 'mega-quisom' : 'mega-serveis';
            } elseif ( ! $has_mega ) {
                // Dropdown Bootstrap (nivell superior o subnivell amb fills).
                $atts['class'] .= ' dropdown-toggle';
                $atts['role'] = 'button';
                $atts['aria-expanded'] = 'false';
                $atts['data-bs-toggle'] = 'dropdown';
                // Evita navegar quan és un pare de desplegable.
                $atts['href'] = '#';
            }
        }

        $attr_html = '';
        foreach ( $atts as $k => $v ) {
            if ( $v === '' ) continue;
            $attr_html .= ' ' . $k . '="' . esc_attr($v) . '"';
        }

        $title = apply_filters( 'the_title', $item->title, $item->ID );
        $output .= '<a' . $attr_html . '>' . esc_html($title) . '</a>';

        // Mega menú render
        if ( $is_mega_quisom && function_exists('fcsd_render_mega_quisom') ) {
            ob_start();
            fcsd_render_mega_quisom();
            $output .= ob_get_clean();
        }

        if ( $is_mega_serveis && function_exists('fcsd_render_mega_serveis') ) {
            ob_start();
            fcsd_render_mega_serveis();
            $output .= ob_get_clean();
        }
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        $output .= "</li>\n";
    }
}
