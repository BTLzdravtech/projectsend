<?php
/**
 * Class that generates a table element.
 *
 * @package    ProjectSend
 * @subpackage Classes
 */

namespace ProjectSend\Classes;

class TableGenerate
{
    private $contents;
    private $current_row;

    public function __construct($attributes)
    {
        $this->contents = self::open($attributes);

        $this->current_row = 1;
    }

    /**
     * Create the form
     * @param $attributes
     * @return string
     */
    public function open($attributes)
    {
        $output = "<table";
        foreach ($attributes as $tag => $value) {
            $output .= ' ' . $tag . '="' . $value . '"';
        }
        $output .= ">\n";
        return $output;
    }

    /**
     * If a column name is sortable, it's content has to be a link
     * to the current page + existing $_GET parameters, but adding
     * the orderby and order ones.
     * If "order" is set and the current column in the loop contains
     * the current sort order needs to be inversed on the link.
     * @param $sort_url
     * @param $is_current_sorted
     * @param $content
     * @return string
     */
    private function buildSortableThContent($sort_url, $is_current_sorted, $content)
    {
        $url_parse = parse_url($_SERVER['REQUEST_URI']);

        if (!empty($_GET)) {
            $new_url_parameters = $_GET;
        }
        $new_url_parameters['orderby'] = $sort_url;

        $order = 'desc';
        if (!empty($new_url_parameters['order'])) {
            $order = ($new_url_parameters['order'] == 'asc') ? 'desc' : 'asc';
            if ($is_current_sorted != true) {
                $order = ($order == 'asc') ? 'desc' : 'asc';
            }
        }
        $new_url_parameters['order'] = $order;

        $params = array();
        foreach ($new_url_parameters as $param => $value) {
            /**
             * Page is not added, so when you click a table header
             * pagination always returns to page 1.
             */
            if ($param != 'page') {
                $params[$param] = $value;
            }
        }
        $query = http_build_query($params);

        $build_url = BASE_URI . basename($url_parse['path']) . '?' . $query;

        $sortable_link = '<a href="' . $build_url . '">';
        $sortable_link .= $content;
        $sortable_link .= '</a>';

        return $sortable_link;
    }

    public function thead($columns)
    {
        $output = "<thead>\n<tr>";
        if (!empty($columns)) {
            foreach ($columns as $column) {
                $continue = (!isset($column['condition']) || !empty($column['condition'])) ? true : false;
                if ($continue == true) {
                    $attributes = (!empty($column['attributes'])) ? $column['attributes'] : array();
                    $data_attr = (!empty($column['data_attr'])) ? $column['data_attr'] : array();
                    $content = (!empty($column['content'])) ? $column['content'] : '';
                    $sortable = (!empty($column['sortable'])) ? $column['sortable'] : false;
                    $sort_url = (!empty($column['sort_url'])) ? $column['sort_url'] : false;
                    $order = (!empty($_GET['order'])) ? html_output($_GET['order']) : 'desc';

                    $is_current_sorted = false;

                    if (!empty($column['hide'])) {
                        $data_attr['hide'] = $column['hide'];
                    }
                    if (isset($_GET['orderby']) && !empty($sort_url)) {
                        if ($_GET['orderby'] == $sort_url) {
                            $attributes['class'][] = 'active';
                            $attributes['class'][] = 'footable-sorted-' . $order;
                            $is_current_sorted = true;
                        }
                    } else {
                        if (!empty($column['sort_default'])) {
                            $attributes['class'][] = 'active';
                            $attributes['class'][] = 'footable-sorted-' . $order;
                            $sort_next = $order;
                        }
                    }

                    if (!empty($column['select_all']) && $column['select_all'] === true) {
                        $content = '<input type="checkbox" name="select_all" id="select_all" value="0" />';
                    }

                    if ($sortable == true && !empty($sort_url)) {
                        $content = self::buildSortableThContent($sort_url, $is_current_sorted, $content);
                    } else {
                        $data_attr['sort-ignore'] = 'true';
                    }

                    /**
                     * Generate the column
                     */
                    $output .= '<th';
                    foreach ($attributes as $tag => $value) {
                        if (is_array($value)) {
                            $value = implode(' ', $value);
                        }
                        $output .= ' ' . $tag . '="' . $value . '"';
                    }
                    foreach ($data_attr as $tag => $value) {
                        $output .= ' data-' . $tag . '="' . $value . '"';
                    }
                    $output .= '>' . $content;

                    if ($sortable == true) {
                        $output .= '<span class="footable-sort-indicator"></span>';
                    }

                    $output .= '</th>';
                }
            }
        }
        $output .= "</tr>\n</thead>\n";
        $this->contents .= $output;
    }

    public function tfoot($columns)
    {
        $output = "<tfoot>\n<tr>";
        if (!empty($columns)) {
            foreach ($columns as $column) {
                $attributes = (!empty($column['attributes'])) ? $column['attributes'] : array();
                $data_attr = (!empty($column['data_attr'])) ? $column['data_attr'] : array();
                $content = (!empty($column['content'])) ? $column['content'] : '';
                /**
                 * Generate the column
                 */
                $output .= '<td';
                foreach ($attributes as $tag => $value) {
                    if (is_array($value)) {
                        $value = implode(' ', $value);
                    }
                    $output .= ' ' . $tag . '="' . $value . '"';
                }
                foreach ($data_attr as $tag => $value) {
                    $output .= ' data-' . $tag . '="' . $value . '"';
                }
                $output .= '>' . $content;

                $output .= '</td>';
            }
        }
        $output .= "</tr>\n</tfoot>\n";
        $this->contents .= $output;
    }

    public function addRow()
    {
        if ($this->current_row == 1) {
            $this->contents .= "<tbody>\n";
        }

        $row_class = ($this->current_row % 2) ? 'table_row' : 'table_row_alt';
        $this->contents .= '<tr class="' . $row_class . '">' . "\n";
        $this->current_row++;
    }

    public function addCell($attributes)
    {
        $continue = (!isset($attributes['condition']) || !empty($attributes['condition'])) ? true : false;

        if ($continue == true) {
            $attributes_local = (!empty($attributes['attributes'])) ? $attributes['attributes'] : array();
            $content = (!empty($attributes['content'])) ? $attributes['content'] : '';
            $is_checkbox = (!empty($attributes['checkbox'])) ? true : false;
            $value = (!empty($attributes['value'])) ? html_output($attributes['value']) : null;

            if ($is_checkbox == true) {
                $content = '<input type="checkbox" class="batch_checkbox" name="batch[]" value="' . $value . '" />' . "\n";
            }

            $this->contents .= "<td";
            if (!empty($attributes_local)) {
                foreach ($attributes_local as $tag => $value) {
                    if (is_array($value)) {
                        $value = implode(' ', $value);
                    }
                    $this->contents .= ' ' . $tag . '="' . $value . '"';
                }
            }
            $this->contents .= ">\n" . $content . "</td>\n";
        }
    }

    public function end_row()
    {
        $this->contents .= "</tr>\n";
    }

    /**
     * Print the full table
     */
    public function render()
    {
        $this->contents .= "</tbody>\n</table>\n";
        return $this->contents;
    }

    /**
     * PAGINATION
     * @param $link
     * @param int $page
     * @return string
     */
    private function constructPaginationLink($link, $page = 1)
    {
        $params['page'] = $page;

        /**
         * List of parameters to ignore when building the pagination links.
         * TODO: change it so it ignores all but 'search' instead? must check
         * if there are other parameters that need to be saved.
         */
        $ignore_current_params = array(
            'page',
            'categories_actions',
            'action',
            'do_action',
            'batch',
        );

        if (!empty($_GET)) {
            foreach ($_GET as $param => $value) {
                if (!in_array($param, $ignore_current_params)) {
                    $params[$param] = $value;
                }
            }
        }
        $query = http_build_query($params);

        return BASE_URI . $link . '?' . $query;
    }

    public function pagination($params)
    {
        if (!is_numeric($params['current'])) {
            $params['current'] = 1;
        } else {
            $params['current'] = (int)$params['current'];
        }

        $output = '';

        if ($params['pages'] > 1) {
            $output = '<div class="container-fluid">
                            <div class="row">
                                <div class="col-xs-12 text-center">
                                    <nav aria-label="' . __('Results navigation', 'cftp_admin') . '">
                                        <div class="pagination_wrapper">
                                            <ul class="pagination">';

            /**
             * First and previous
             */
            if ($params['current'] > 1) {
                $output .= '<li>
                                <a href="' . self::constructPaginationLink($params['link']) . '" data-page="first"><span aria-hidden="true">&laquo;</span></a>
                            </li>
                            <li>
                                <a href="' . self::constructPaginationLink($params['link'], $params['current'] - 1) . '" data-page="prev">&lsaquo;</a>
                            </li>';
            }

            /**
             * Pages
             */
            $already_spaced = false;
            for ($i = 1; $i <= $params['pages']; $i++) {
                if (($i < $params['current'] - 3 || $i > $params['current'] + 3)
                    && ($i != 1 && $i != $params['pages'])
                ) {
                    if ($already_spaced == false) {
                        $output .= '<li class="disabled"><a href="#">...</a></li>';
                        $already_spaced = true;
                    }
                    continue;
                }
                if ($params['current'] == $i) {
                    $output .= '<li class="active"><a href="#">' . $i . '</a></li>';
                } else {
                    $output .= '<li><a href="' . self::constructPaginationLink($params['link'], $i) . '">' . $i . '</a></li>';
                }

                if ($i > $params['current']) {
                    $already_spaced = false;
                }
            }

            /**
             * Next and last
             */
            if ($params['current'] != $params['pages']) {
                $output .= '<li>
                                <a href="' . self::constructPaginationLink($params['link'], $params['current'] + 1) . '" data-page="next">&rsaquo;</a>
                            </li>
                            <li>
                                <a href="' . self::constructPaginationLink($params['link'], $params['pages']) . '" data-page="last"><span aria-hidden="true">&raquo;</span></a>
                            </li>';
            }


            $output .= '</ul>
                    </div>
                </nav>';

            $output .= '<div class="go_to_page">
                            <div class="form-group">
                                <label class="control-label hidden-xs hidden-sm">' . __('Go to:', 'cftp_admin') . '</label>
                                <input type="text" class="form-control" name="page" id="page_number" data-link="' . self::constructPaginationLink($params['link'], '_pgn_') . '" value="' . $params['current'] . '" />
                            </div>
                            <div class="form-group">
                                <button type="button" class="form-control"><span aria-hidden="true" class="glyphicon glyphicon-ok"></span></button>
                            </div>
                        </div>';

            $output .= '</div>
                    </div>
                </div>';
        }

        return $output;
    }
}
