<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Interaction\Output\Types;

use Schilffarth\Console\{
    Source\Component\Interaction\Output\AbstractOutputObject,
    Source\Component\Interaction\Output\Output
};

/**
 * todo Clean up this class, it's ugly as fuck
 *
 * todo...
 *
 * At the moment, you can only set color scheme for each columns label and entries
 * todo Provide capability to set general label color (respectively coloring by row...)
 *
 * You can define some repeating row coloring, for example row 1, 3, 5 colored red, row 2, 4, 6 colored green
 * todo Repeated row coloring
 */
class Grid extends AbstractOutputObject
{

    /**
     * Types for $repeatScheme
     */
    public const REPEAT_ROW = 'row';
    public const REPEAT_COLUMN = 'column';

    /**
     * Whether to display column labels or not
     */
    private $suppressLabels = false;

    /**
     * array(
     *     'id_one' => array(
     *         'label' => 'Label One',
     *         'pad' => 20,
     *         'max_len' => 0
     *     ),
     *     'id_two' => array(
     *         'label' => 'Label Two',
     *         'pad' => 50,
     *         'max_len' => 0
     *     )
     * );
     */
    private $columns = [];

    /**
     * array(
     *     array(
     *         // Keys are mapped to the column in @see Grid::columns
     *         'id_one' => 'Test value for first column',
     *         'id_two' => 'Second column value here'
     *     ),
     *     array(
     *         'id_one' => 'Another value in the second row for this grid',
     *         'id_two' => ''
     *     )
     * );
     */
    private $rows = [];

    /**
     * array(
     *     'column_id' => array(
     *         'header' => 'info',
     *         'data' => 'comment'
     *     )
     * );
     *
     * The given example would wrap the header with <info /> tags and the column entries with <comment /> tags
     */
    private $colored = [];

    /**
     * todo This does not do anything at the moment
     *
     * either:
     *     array(
     *         'type' => 'column'
     *         'data' => array(
     *             'tag_one',
     *             'tag_two'
     *         )
     *     );
     * or:
     *     array(
     *         'type' => 'row'
     *         'data' => array(
     *             'tag_one',
     *             'tag_two',
     *             'tag_three'
     *         )
     *     );
     *
     * If type === column
     *     The given example would wrap the first column entries with <tag_one/>, the second column <tag_two/> and
     *     iterate over every column and wrap it in the specified order of tags
     *
     * If type === row
     *     The same as for columns, would wrap first row in <tag_one/>, second row <tag_two/>, third row <tag_three/>,
     *     fourth row <tag_one/>, fifth row <tag_two/> and so on
     */
    private $repeatScheme = [];

    public $output;

    public function __construct(
        Output $output
    ) {
        $this->output = $output;
    }

    /**
     * Adds a column to @see Grid::columns
     */
    public function addColumn(string $id, string $label = '', int $pad = 0): self
    {
        $this->columns[$id] = ['label' => $label, 'pad' => $pad, 'max_len' => 0];

        return $this;
    }


    /**
     * Adds a row to @see Grid::rows
     */
    public function addRow(array $args): self
    {
        $row = [];

        foreach ($args as $columnId => $columnData) {
            $row[$columnId] = $columnData;
        }

        $this->rows[] = $row;

        return $this;
    }

    /**
     * Set an output style for the column, format @see Grid::colored
     */
    public function addColorScheme(string $columnId, string $headerTag, string $dataTag): self
    {
        $this->colored[$columnId] = ['header' => $headerTag, 'data' => $dataTag];

        return $this;
    }

    /**
     * todo This does not do anything at the moment
     * Register a new repeat scheme, for details @see Grid::repeatScheme
     */
    public function addRepeatingColorScheme(array $repeat, string $type): self
    {
        $this->repeatScheme = [
            'type' => $type,
            'data' => $repeat
        ];

        return $this;
    }

    /**
     * Do not display column labels
     */
    public function suppressColumnLabels(): self
    {
        $this->suppressLabels = true;

        return $this;
    }

    /**
     * Build and display the grid
     */
    public function display(int $verbosity = Output::NORMAL): void
    {
        $this->setPaddings();

        $output = '';

        if (!$this->suppressLabels) {
            // Column labels / headers
            foreach ($this->columns as $columnId => $columnData) {
                $output .= $this->getPaddedStr($this->colored[$columnId] ?? [], $columnData['label'], $columnData['pad'], 'header');
            }

            $output .= PHP_EOL;
        }

        // Grid rows / data
        foreach ($this->rows as $row) {
            foreach ($this->columns as $columnId => $columnData) {
                $output .= $this->getPaddedStr($this->colored[$columnId] ?? [], $row[$columnId], $columnData['pad'], 'data');
            }

            $output .= PHP_EOL;
        }

        $this->output->write($output, $verbosity);
    }

    /**
     * If a column data entry length is longer than the column padding, the column padding will be increased
     */
    private function setPaddings(): void
    {
        foreach ($this->columns as $columnId => $columnData) {
            if (strlen($columnData['label']) > $columnData['pad']) {
                // Increase the column width by exceeded label length
                $this->columns[$columnId]['pad'] = strlen($columnData['label']) + 2;
            }

            foreach ($this->rows as $row) {
                $dataLen = strlen($row[$columnId]);

                if ($dataLen > $this->columns[$columnId]['max_len']) {
                    // Update max length
                    $this->columns[$columnId]['max_len'] = $dataLen;
                }

                // The columns should not be stick together, this ensures at least 2 white spaces added between columns
                $dataLen = $dataLen + 2;

                if ($dataLen > $this->columns[$columnId]['pad']) {
                    // If max length exceeds the current padding, increase padding
                    $this->columns[$columnId]['pad'] = $dataLen;
                }
            }
        }
    }

    /**
     * Pad a grid entry and apply color scheme, if specified
     */
    private function getPaddedStr(array $color, string $str, int $pad, string $key): string
    {
        $output = '';

        if (isset($color[$key]) && $color[$key]) {
            // Column label / header should be colored
            $oldLength = strlen($str);

            // Wrap the label with the desired tags
            $str = $this->output->write(
                sprintf('<%1$s>%2$s</%1$s>', $color[$key], $str),
                Output::NORMAL,
                false,
                true
            );

            // Increase the column padding
            $pad += strlen($str) - $oldLength;
        }

        return $output . str_pad($str . ' ', $pad);
    }

}
