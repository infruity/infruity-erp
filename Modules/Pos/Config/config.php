<?php

return [
    'name' => 'Pos',

    // Paper width of the printed nota (CSS length, e.g. 58mm, 80mm, 210mm). Set NOTA_PAPER_WIDTH in .env once the printer is known.
    'nota_paper_width' => env('NOTA_PAPER_WIDTH', '80mm'),
];
