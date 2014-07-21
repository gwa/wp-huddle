<?php
namespace gwa\wordpress;

class MultiMetaOption
{
    private $_post;
    private $_metakey;

    public function __construct( $post, $metakey )
    {
        $this->_post = $post;
        $this->_metakey = $metakey;
    }

    public function getControlMarkup( $label )
    {
        $options = $this->getAllOptions();
        $set = $this->getSetOptions($this->_post);

        $markup  = '';
        $markup .= '<div class="gwawp-'.$this->_metakey.'">';
        $markup .= '<label for="gwawp_'.$this->_metakey.'">'.$label.':</label>';
        foreach ($options as $option) {
            $sel = in_array($option, $set) ? ' checked' : '';
            $markup .= '<div><input type="checkbox" name="gwawp_'.$this->_metakey.'[]" value="'.addslashes($option).'"'.$sel.' /> '.$option.'</div>';
        }
        $markup .= '</div>';
        $markup .= '<div><label for="gwawp_'.$this->_metakey.'new">Add new:</label> <input type="text" name="gwawp_'.$this->_metakey.'new" /></div>';
        return $markup;
    }

    public function getAllOptions()
    {
        global $wpdb;
        $query = "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key='".$this->_metakey."' ORDER BY meta_key";
        $results = $wpdb->get_results($query);
        $alloptions = array();
        foreach ($results as $r) {
            $alloptions[] = $r->meta_value;
        }
        sort($alloptions);
        return $alloptions;
    }

    /**
     * Returns an array of selected options.
     * @param  object         $post WP Post object
     * @return array
     */
    public function getSetOptions( $post=null )
    {
        if (!$post) {
            $post = $this->_post;
        }
        if (is_int($post)) {
            $custom = get_post_custom($post);
        } else {
            $custom = get_post_custom($post->ID);
        }
        if (!isset($custom[$this->_metakey])) {
            return array();
        }
        $set = $custom[$this->_metakey];
        return is_array($set) ? $set : array($set);
    }

    public function handlePost( $post_id, $postvars )
    {
        $this->_handleEdit($post_id, $postvars);
        $this->_handleNew($post_id, $postvars);
    }

    private function _handleEdit( $post_id, $postvars )
    {
        // get current options
        $set = $this->getSetOptions($post_id);
        $optionsupdated = array();
        if (isset($postvars['gwawp_'.$this->_metakey]) && is_array($postvars['gwawp_'.$this->_metakey])) {
            foreach ($postvars['gwawp_'.$this->_metakey] as $option) {
                $optionsupdated[] = $option;
                // if option not already set, set it.
                if (!in_array($option, $set)) {
                    add_post_meta($post_id, $this->_metakey, $option);
                }
            }
        }
        // delete all non-updated options
        foreach ($set as $option) {
            if (!in_array($option, $optionsupdated)) {
                delete_post_meta($post_id, $this->_metakey, $option);
            }
        }
    }

    private function _handleNew( $post_id, $postvars )
    {
        if (!isset($postvars['gwawp_'.$this->_metakey.'new']) || trim($postvars['gwawp_'.$this->_metakey.'new'])=='') {
            return;
        }

        $set = $this->getSetOptions($post_id);
        $newoptions = explode(',', $postvars['gwawp_'.$this->_metakey.'new']);
        foreach ($newoptions as $newoption) {
            $newoption = trim($newoption);
            if ($newoption && !in_array($newoption, $set)) {
                add_post_meta($post_id, $this->_metakey, trim($newoption));
            }
        }
    }
}
