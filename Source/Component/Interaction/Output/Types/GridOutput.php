<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Interaction\Output\Types;

use Schilffarth\CommandLineInterface\{
    Source\Component\Interaction\Output\AbstractOutputObject,
    Source\Component\Interaction\Output\Output
};

// todo Code this class more nicely
// It's way too ugly at the moment

class GridOutput extends AbstractOutputObject
{

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
     *         // Keys are mapped to the column in @see GridOutput::columns
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

    private $output;

    public function __construct(
        Output $output
    ) {
        $this->output = $output;
    }

    /**
     * Adds a column to @see GridOutput::columns
     */
    public function addColumn(string $id, string $label = '', int $pad = 0): self
    {
        $this->columns[$id] = ['label' => $label, 'pad' => $pad, 'max_len' => 0];

        return $this;
    }


    /**
     * Adds a row to @see GridOutput::rows
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
     * Set an output style for the column, format @see GridOutput::colored
     */
    public function addColorScheme(string $columnId, string $headerTag, string $dataTag): self
    {
        $this->colored[$columnId] = ['header' => $headerTag, 'data' => $dataTag];

        return $this;
    }

    /**
     * Build and display the grid
     */
    public function display(): void
    {
        $output = '';

        $this->setPaddings();

        // Column labels / headers
        foreach ($this->columns as $columnId => $columnData) {
            if (isset($this->colored[$columnId]) && $this->colored[$columnId]['header']) {
                // Column label / header should be colored
                $oldLength = strlen($columnData['label']);
                // Wrap the label with the desired tags
                $columnData['label'] = $this->output->write(
                    sprintf('<%1$s>%2$s</%1$s>', $this->colored[$columnId]['header'], $columnData['label']),
                    Output::NORMAL,
                    false,
                    true
                );
                $newLength = strlen($columnData['label']);
                // Increase the column padding
                $columnData['pad'] += $newLength - $oldLength;
            }

            $output .= str_pad($columnData['label'] . ' ', $columnData['pad']);
        }

        $output .= PHP_EOL;

        // Grid rows / data
        foreach ($this->rows as $row) {
            foreach ($this->columns as $columnId => $columnData) {
                if (isset($this->colored[$columnId]) && $this->colored[$columnId]['data']) {
                    // For comments see the column labels / header formatting "foreach" from line 120
                    $oldLength = strlen($row[$columnId]);
                    $row[$columnId] = $this->output->write(
                        sprintf('<%1$s>%2$s</%1$s>', $this->colored[$columnId]['data'], $row[$columnId]),
                        Output::NORMAL,
                        false,
                        true
                    );
                    $newLength = strlen($this->output->write($row[$columnId], Output::NORMAL, false, true));
                    $columnData['pad'] += $newLength - $oldLength;
                }

                $output .= str_pad($row[$columnId], $columnData['pad']);
            }

            $output .= PHP_EOL;
        }

        $this->output->write($output);

        die;
    }

    /**
     * If a column data entry length is longer than the column padding, the column padding will be increased
     */
    private function setPaddings(): void
    {
        foreach ($this->rows as $row) {
            foreach ($this->columns as $columnId => $columnData) {
                $dataLen = strlen($row[$columnId]);

                if ($dataLen > $this->columns[$columnId]['max_len']) {
                    // Update max length
                    $this->columns[$columnId]['max_len'] = $dataLen;
                }

                // The columns should not be sticked together, this ensures at least 2 white spaces added between columns
                $dataLen = $dataLen + 2;

                if ($dataLen > $this->columns[$columnId]['pad']) {
                    // If max length exceeds the current padding, increase padding
                    $this->columns[$columnId]['pad'] = $dataLen;
                }
            }
        }
    }

}
