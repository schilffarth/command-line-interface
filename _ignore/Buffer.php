<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Component;

/**
 * todo Buffer
 *
 * Temporary storage for all data during the execution of a console command
 *
 * This offers the possibility to access ALL data that has been used, calculated, outputted and inputted after the
 * command was run
 *
 * All data can be added with @see Buffer::addData
 * Data cannot be removed - There shouldn't be a reason to do so anyway
 * When an error occurs, both intended and unintended, actually whenever you want, you can access the buffers data
 * with @see Buffer::getData
 */
class Buffer
{

    private $data = [];

    public function addData(string $data)
    {
        $this->data[$data];
    }

    public function writeData()
    {
        $data = var_export($this->data, true);
    }

    public function dumpData()
    {
        $this->addData('Dumping data of ' . __CLASS__ . __METHOD__ . ' in ' . __LINE__);

        var_dump($this->data);

        $this->addData('Dumped buffer successfully.');
    }

}
