<?php

namespace RegularLabs\Scoped\Intervention\Image\Gd\Commands;

use RegularLabs\Scoped\Intervention\Image\Commands\AbstractCommand;
class GreyscaleCommand extends AbstractCommand
{
    /**
     * Turns an image into a greyscale version
     *
     * @param  \Intervention\Image\Image $image
     * @return boolean
     */
    public function execute($image)
    {
        return imagefilter($image->getCore(), \IMG_FILTER_GRAYSCALE);
    }
}
