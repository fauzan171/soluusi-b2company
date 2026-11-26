<?php

// DISC 24 scoring tables (client xlsx extraction). Used by InstrumentScoring
// scoring_type disc_forced (instrument code disc24).
// key: item no (1-24) => {P: [letter per statement, '*'=netral], K: [...]}
// conv: p/k = count(0-20) => [D,I,S,C] line; diff = diff+22 (range -22..22) => line
// types: 40 profil {key (== flag index 1-40), combo, archetype, traits, jobs, desc}
return [
  'key' => 
  [
    1 => 
    [
      'P' => 
      [
        0 => 'S',
        1 => 'I',
        2 => '*',
        3 => 'C',
      ],
      'K' => 
      [
        0 => 'S',
        1 => 'I',
        2 => 'D',
        3 => 'C',
      ],
    ],
    2 => 
    [
      'P' => 
      [
        0 => 'C',
        1 => 'D',
        2 => '*',
        3 => 'S',
      ],
      'K' => 
      [
        0 => '*',
        1 => 'D',
        2 => 'I',
        3 => 'S',
      ],
    ],
    3 => 
    [
      'P' => 
      [
        0 => 'I',
        1 => '*',
        2 => '*',
        3 => 'D',
      ],
      'K' => 
      [
        0 => 'I',
        1 => 'C',
        2 => 'S',
        3 => '*',
      ],
    ],
    4 => 
    [
      'P' => 
      [
        0 => 'C',
        1 => 'S',
        2 => '*',
        3 => 'D',
      ],
      'K' => 
      [
        0 => 'C',
        1 => 'S',
        2 => 'I',
        3 => 'D',
      ],
    ],
    5 => 
    [
      'P' => 
      [
        0 => 'I',
        1 => 'D',
        2 => 'S',
        3 => '*',
      ],
      'K' => 
      [
        0 => '*',
        1 => 'D',
        2 => 'S',
        3 => 'C',
      ],
    ],
    6 => 
    [
      'P' => 
      [
        0 => 'C',
        1 => 'D',
        2 => 'I',
        3 => 'S',
      ],
      'K' => 
      [
        0 => '*',
        1 => 'D',
        2 => 'I',
        3 => 'S',
      ],
    ],
    7 => 
    [
      'P' => 
      [
        0 => 'S',
        1 => 'I',
        2 => '*',
        3 => '*',
      ],
      'K' => 
      [
        0 => '*',
        1 => 'I',
        2 => 'C',
        3 => 'D',
      ],
    ],
    8 => 
    [
      'P' => 
      [
        0 => 'I',
        1 => 'S',
        2 => 'C',
        3 => 'D',
      ],
      'K' => 
      [
        0 => 'I',
        1 => 'S',
        2 => 'C',
        3 => 'D',
      ],
    ],
    9 => 
    [
      'P' => 
      [
        0 => 'D',
        1 => 'C',
        2 => '*',
        3 => '*',
      ],
      'K' => 
      [
        0 => 'D',
        1 => 'C',
        2 => 'I',
        3 => 'S',
      ],
    ],
    10 => 
    [
      'P' => 
      [
        0 => '*',
        1 => 'D',
        2 => 'S',
        3 => 'I',
      ],
      'K' => 
      [
        0 => 'C',
        1 => 'D',
        2 => 'S',
        3 => '*',
      ],
    ],
    11 => 
    [
      'P' => 
      [
        0 => 'S',
        1 => '*',
        2 => 'D',
        3 => 'C',
      ],
      'K' => 
      [
        0 => '*',
        1 => 'I',
        2 => 'D',
        3 => 'C',
      ],
    ],
    12 => 
    [
      'P' => 
      [
        0 => '*',
        1 => 'C',
        2 => 'I',
        3 => 'D',
      ],
      'K' => 
      [
        0 => 'S',
        1 => '*',
        2 => 'I',
        3 => 'D',
      ],
    ],
    13 => 
    [
      'P' => 
      [
        0 => 'D',
        1 => 'S',
        2 => 'I',
        3 => '*',
      ],
      'K' => 
      [
        0 => 'D',
        1 => '*',
        2 => '*',
        3 => 'C',
      ],
    ],
    14 => 
    [
      'P' => 
      [
        0 => 'C',
        1 => 'I',
        2 => 'S',
        3 => 'D',
      ],
      'K' => 
      [
        0 => 'C',
        1 => 'I',
        2 => '*',
        3 => 'D',
      ],
    ],
    15 => 
    [
      'P' => 
      [
        0 => 'S',
        1 => 'C',
        2 => 'I',
        3 => 'D',
      ],
      'K' => 
      [
        0 => 'S',
        1 => '*',
        2 => 'I',
        3 => 'D',
      ],
    ],
    16 => 
    [
      'P' => 
      [
        0 => '*',
        1 => 'C',
        2 => 'I',
        3 => 'S',
      ],
      'K' => 
      [
        0 => 'D',
        1 => '*',
        2 => 'I',
        3 => 'S',
      ],
    ],
    17 => 
    [
      'P' => 
      [
        0 => '*',
        1 => 'D',
        2 => 'S',
        3 => 'I',
      ],
      'K' => 
      [
        0 => 'C',
        1 => 'D',
        2 => 'S',
        3 => '*',
      ],
    ],
    18 => 
    [
      'P' => 
      [
        0 => 'D',
        1 => '*',
        2 => '*',
        3 => 'C',
      ],
      'K' => 
      [
        0 => 'D',
        1 => 'I',
        2 => 'S',
        3 => '*',
      ],
    ],
    19 => 
    [
      'P' => 
      [
        0 => 'D',
        1 => 'S',
        2 => 'I',
        3 => '*',
      ],
      'K' => 
      [
        0 => 'D',
        1 => '*',
        2 => 'I',
        3 => 'C',
      ],
    ],
    20 => 
    [
      'P' => 
      [
        0 => 'D',
        1 => 'S',
        2 => 'I',
        3 => 'C',
      ],
      'K' => 
      [
        0 => '*',
        1 => 'S',
        2 => 'I',
        3 => '*',
      ],
    ],
    21 => 
    [
      'P' => 
      [
        0 => 'S',
        1 => 'D',
        2 => 'I',
        3 => '*',
      ],
      'K' => 
      [
        0 => 'S',
        1 => 'D',
        2 => 'I',
        3 => 'C',
      ],
    ],
    22 => 
    [
      'P' => 
      [
        0 => 'S',
        1 => '*',
        2 => 'D',
        3 => 'C',
      ],
      'K' => 
      [
        0 => 'S',
        1 => 'I',
        2 => 'D',
        3 => 'C',
      ],
    ],
    23 => 
    [
      'P' => 
      [
        0 => '*',
        1 => 'I',
        2 => 'S',
        3 => '*',
      ],
      'K' => 
      [
        0 => 'D',
        1 => '*',
        2 => 'S',
        3 => 'C',
      ],
    ],
    24 => 
    [
      'P' => 
      [
        0 => '*',
        1 => 'I',
        2 => 'D',
        3 => 'C',
      ],
      'K' => 
      [
        0 => 'S',
        1 => 'I',
        2 => '*',
        3 => '*',
      ],
    ],
  ],
  'conv' => 
  [
    'p' => 
    [
      0 => 
      [
        0 => -6.0,
        1 => -7.0,
        2 => -5.70000000000000017763568394002504646778106689453125,
        3 => -6.0,
      ],
      1 => 
      [
        0 => -5.29999999999999982236431605997495353221893310546875,
        1 => -4.5999999999999996447286321199499070644378662109375,
        2 => -4.29999999999999982236431605997495353221893310546875,
        3 => -4.70000000000000017763568394002504646778106689453125,
      ],
      2 => 
      [
        0 => -4.0,
        1 => -2.5,
        2 => -3.5,
        3 => -3.5,
      ],
      3 => 
      [
        0 => -2.5,
        1 => -1.3000000000000000444089209850062616169452667236328125,
        2 => -1.5,
        3 => -1.5,
      ],
      4 => 
      [
        0 => -1.6999999999999999555910790149937383830547332763671875,
        1 => 1.0,
        2 => -0.6999999999999999555910790149937383830547332763671875,
        3 => 0.5,
      ],
      5 => 
      [
        0 => -1.3000000000000000444089209850062616169452667236328125,
        1 => 3.0,
        2 => 0.5,
        3 => 2.0,
      ],
      6 => 
      [
        0 => 0.0,
        1 => 3.5,
        2 => 1.0,
        3 => 3.0,
      ],
      7 => 
      [
        0 => 0.5,
        1 => 5.29999999999999982236431605997495353221893310546875,
        2 => 2.5,
        3 => 5.29999999999999982236431605997495353221893310546875,
      ],
      8 => 
      [
        0 => 1.0,
        1 => 5.70000000000000017763568394002504646778106689453125,
        2 => 3.0,
        3 => 5.70000000000000017763568394002504646778106689453125,
      ],
      9 => 
      [
        0 => 2.0,
        1 => 6.0,
        2 => 4.0,
        3 => 6.0,
      ],
      10 => 
      [
        0 => 3.0,
        1 => 6.5,
        2 => 4.5999999999999996447286321199499070644378662109375,
        3 => 6.29999999999999982236431605997495353221893310546875,
      ],
      11 => 
      [
        0 => 3.5,
        1 => 7.0,
        2 => 5.0,
        3 => 6.5,
      ],
      12 => 
      [
        0 => 4.0,
        1 => 7.0,
        2 => 5.70000000000000017763568394002504646778106689453125,
        3 => 6.70000000000000017763568394002504646778106689453125,
      ],
      13 => 
      [
        0 => 4.70000000000000017763568394002504646778106689453125,
        1 => 7.0,
        2 => 6.0,
        3 => 7.0,
      ],
      14 => 
      [
        0 => 5.29999999999999982236431605997495353221893310546875,
        1 => 7.0,
        2 => 6.5,
        3 => 7.29999999999999982236431605997495353221893310546875,
      ],
      15 => 
      [
        0 => 6.5,
        1 => 7.0,
        2 => 6.5,
        3 => 7.29999999999999982236431605997495353221893310546875,
      ],
      16 => 
      [
        0 => 7.0,
        1 => 7.5,
        2 => 7.0,
        3 => 7.29999999999999982236431605997495353221893310546875,
      ],
      17 => 
      [
        0 => 7.0,
        1 => 7.5,
        2 => 7.0,
        3 => 7.5,
      ],
      18 => 
      [
        0 => 7.0,
        1 => 7.5,
        2 => 7.0,
        3 => 8.0,
      ],
      19 => 
      [
        0 => 7.5,
        1 => 7.5,
        2 => 7.5,
        3 => 8.0,
      ],
      20 => 
      [
        0 => 7.5,
        1 => 8.0,
        2 => 7.5,
        3 => 8.0,
      ],
    ],
    'k' => 
    [
      0 => 
      [
        0 => 7.5,
        1 => 7.0,
        2 => 7.5,
        3 => 7.5,
      ],
      1 => 
      [
        0 => 6.5,
        1 => 6.0,
        2 => 7.0,
        3 => 7.0,
      ],
      2 => 
      [
        0 => 4.29999999999999982236431605997495353221893310546875,
        1 => 4.0,
        2 => 6.0,
        3 => 5.5999999999999996447286321199499070644378662109375,
      ],
      3 => 
      [
        0 => 2.5,
        1 => 2.5,
        2 => 4.0,
        3 => 4.0,
      ],
      4 => 
      [
        0 => 1.5,
        1 => 0.5,
        2 => 2.5,
        3 => 2.5,
      ],
      5 => 
      [
        0 => 0.5,
        1 => 0.0,
        2 => 1.5,
        3 => 1.5,
      ],
      6 => 
      [
        0 => 0.0,
        1 => -2.0,
        2 => 0.5,
        3 => 0.5,
      ],
      7 => 
      [
        0 => -1.3000000000000000444089209850062616169452667236328125,
        1 => -3.5,
        2 => -1.3000000000000000444089209850062616169452667236328125,
        3 => 0.0,
      ],
      8 => 
      [
        0 => -1.5,
        1 => -4.29999999999999982236431605997495353221893310546875,
        2 => -2.0,
        3 => -1.3000000000000000444089209850062616169452667236328125,
      ],
      9 => 
      [
        0 => -2.5,
        1 => -5.29999999999999982236431605997495353221893310546875,
        2 => -3.0,
        3 => -2.5,
      ],
      10 => 
      [
        0 => -3.0,
        1 => -6.0,
        2 => -4.29999999999999982236431605997495353221893310546875,
        3 => -3.5,
      ],
      11 => 
      [
        0 => -3.5,
        1 => -6.5,
        2 => -5.29999999999999982236431605997495353221893310546875,
        3 => -5.29999999999999982236431605997495353221893310546875,
      ],
      12 => 
      [
        0 => -4.29999999999999982236431605997495353221893310546875,
        1 => -7.0,
        2 => -6.0,
        3 => -5.70000000000000017763568394002504646778106689453125,
      ],
      13 => 
      [
        0 => -5.29999999999999982236431605997495353221893310546875,
        1 => -7.20000000000000017763568394002504646778106689453125,
        2 => -6.5,
        3 => -6.0,
      ],
      14 => 
      [
        0 => -5.70000000000000017763568394002504646778106689453125,
        1 => -7.20000000000000017763568394002504646778106689453125,
        2 => -6.70000000000000017763568394002504646778106689453125,
        3 => -6.5,
      ],
      15 => 
      [
        0 => -6.0,
        1 => -7.20000000000000017763568394002504646778106689453125,
        2 => -6.70000000000000017763568394002504646778106689453125,
        3 => -7.0,
      ],
      16 => 
      [
        0 => -6.5,
        1 => -7.29999999999999982236431605997495353221893310546875,
        2 => -7.0,
        3 => -7.29999999999999982236431605997495353221893310546875,
      ],
      17 => 
      [
        0 => 6.70000000000000017763568394002504646778106689453125,
        1 => -7.29999999999999982236431605997495353221893310546875,
        2 => -7.20000000000000017763568394002504646778106689453125,
        3 => -7.5,
      ],
      18 => 
      [
        0 => 7.0,
        1 => -7.29999999999999982236431605997495353221893310546875,
        2 => -7.29999999999999982236431605997495353221893310546875,
        3 => -7.70000000000000017763568394002504646778106689453125,
      ],
      19 => 
      [
        0 => -7.29999999999999982236431605997495353221893310546875,
        1 => -7.5,
        2 => -7.5,
        3 => -7.9000000000000003552713678800500929355621337890625,
      ],
      20 => 
      [
        0 => -7.5,
        1 => -8.0,
        2 => -8.0,
        3 => -8.0,
      ],
    ],
    'diff' => 
    [
      0 => 
      [
        0 => -8.0,
        1 => -8.0,
        2 => -8.0,
        3 => -7.5,
      ],
      1 => 
      [
        0 => -7.5,
        1 => -8.0,
        2 => -8.0,
        3 => -7.29999999999999982236431605997495353221893310546875,
      ],
      2 => 
      [
        0 => -7.0,
        1 => -8.0,
        2 => -8.0,
        3 => -7.29999999999999982236431605997495353221893310546875,
      ],
      3 => 
      [
        0 => -6.79999999999999982236431605997495353221893310546875,
        1 => -8.0,
        2 => -8.0,
        3 => -7.0,
      ],
      4 => 
      [
        0 => -6.75,
        1 => -7.0,
        2 => -7.5,
        3 => -6.70000000000000017763568394002504646778106689453125,
      ],
      5 => 
      [
        0 => -6.70000000000000017763568394002504646778106689453125,
        1 => -6.70000000000000017763568394002504646778106689453125,
        2 => -7.29999999999999982236431605997495353221893310546875,
        3 => -6.70000000000000017763568394002504646778106689453125,
      ],
      6 => 
      [
        0 => -6.5,
        1 => -6.70000000000000017763568394002504646778106689453125,
        2 => -7.29999999999999982236431605997495353221893310546875,
        3 => -6.70000000000000017763568394002504646778106689453125,
      ],
      7 => 
      [
        0 => -6.29999999999999982236431605997495353221893310546875,
        1 => -6.70000000000000017763568394002504646778106689453125,
        2 => -7.0,
        3 => -6.5,
      ],
      8 => 
      [
        0 => -6.0999999999999996447286321199499070644378662109375,
        1 => -6.70000000000000017763568394002504646778106689453125,
        2 => -6.5,
        3 => -6.29999999999999982236431605997495353221893310546875,
      ],
      9 => 
      [
        0 => -5.9000000000000003552713678800500929355621337890625,
        1 => -6.70000000000000017763568394002504646778106689453125,
        2 => -6.5,
        3 => -6.0,
      ],
      10 => 
      [
        0 => -5.70000000000000017763568394002504646778106689453125,
        1 => -6.70000000000000017763568394002504646778106689453125,
        2 => -6.5,
        3 => -5.8499999999999996447286321199499070644378662109375,
      ],
      11 => 
      [
        0 => -5.29999999999999982236431605997495353221893310546875,
        1 => -6.70000000000000017763568394002504646778106689453125,
        2 => -6.5,
        3 => -5.8499999999999996447286321199499070644378662109375,
      ],
      12 => 
      [
        0 => -4.29999999999999982236431605997495353221893310546875,
        1 => -6.5,
        2 => -6.0,
        3 => -5.70000000000000017763568394002504646778106689453125,
      ],
      13 => 
      [
        0 => -3.5,
        1 => -6.0,
        2 => -4.70000000000000017763568394002504646778106689453125,
        3 => -4.70000000000000017763568394002504646778106689453125,
      ],
      14 => 
      [
        0 => -3.25,
        1 => -5.70000000000000017763568394002504646778106689453125,
        2 => -4.29999999999999982236431605997495353221893310546875,
        3 => -4.29999999999999982236431605997495353221893310546875,
      ],
      15 => 
      [
        0 => -3.0,
        1 => -4.70000000000000017763568394002504646778106689453125,
        2 => -3.5,
        3 => -3.5,
      ],
      16 => 
      [
        0 => -2.75,
        1 => -4.29999999999999982236431605997495353221893310546875,
        2 => -3.0,
        3 => -3.0,
      ],
      17 => 
      [
        0 => -2.5,
        1 => -3.5,
        2 => -2.0,
        3 => -2.5,
      ],
      18 => 
      [
        0 => -1.5,
        1 => -3.0,
        2 => -1.5,
        3 => -0.5,
      ],
      19 => 
      [
        0 => -1.0,
        1 => -2.0,
        2 => -1.0,
        3 => 0.0,
      ],
      20 => 
      [
        0 => -0.5,
        1 => -1.5,
        2 => -0.5,
        3 => 0.299999999999999988897769753748434595763683319091796875,
      ],
      21 => 
      [
        0 => -0.25,
        1 => 0.0,
        2 => 0.0,
        3 => 0.5,
      ],
      22 => 
      [
        0 => 0.0,
        1 => 0.5,
        2 => 1.0,
        3 => 1.5,
      ],
      23 => 
      [
        0 => 0.5,
        1 => 1.0,
        2 => 1.5,
        3 => 3.0,
      ],
      24 => 
      [
        0 => 0.6999999999999999555910790149937383830547332763671875,
        1 => 1.5,
        2 => 2.0,
        3 => 4.0,
      ],
      25 => 
      [
        0 => 1.0,
        1 => 3.0,
        2 => 3.0,
        3 => 4.29999999999999982236431605997495353221893310546875,
      ],
      26 => 
      [
        0 => 1.3000000000000000444089209850062616169452667236328125,
        1 => 4.0,
        2 => 3.5,
        3 => 5.5,
      ],
      27 => 
      [
        0 => 1.5,
        1 => 4.29999999999999982236431605997495353221893310546875,
        2 => 4.0,
        3 => 5.70000000000000017763568394002504646778106689453125,
      ],
      28 => 
      [
        0 => 2.0,
        1 => 5.0,
        2 => 6.0,
        3 => 0.0,
      ],
      29 => 
      [
        0 => 2.5,
        1 => 5.5,
        2 => 4.70000000000000017763568394002504646778106689453125,
        3 => 6.29999999999999982236431605997495353221893310546875,
      ],
      30 => 
      [
        0 => 3.5,
        1 => 6.5,
        2 => 5.0,
        3 => 6.5,
      ],
      31 => 
      [
        0 => 4.0,
        1 => 6.70000000000000017763568394002504646778106689453125,
        2 => 5.5,
        3 => 6.70000000000000017763568394002504646778106689453125,
      ],
      32 => 
      [
        0 => 4.70000000000000017763568394002504646778106689453125,
        1 => 7.0,
        2 => 6.0,
        3 => 7.0,
      ],
      33 => 
      [
        0 => 4.8499999999999996447286321199499070644378662109375,
        1 => 7.29999999999999982236431605997495353221893310546875,
        2 => 6.20000000000000017763568394002504646778106689453125,
        3 => 7.29999999999999982236431605997495353221893310546875,
      ],
      34 => 
      [
        0 => 5.0,
        1 => 7.29999999999999982236431605997495353221893310546875,
        2 => 6.29999999999999982236431605997495353221893310546875,
        3 => 7.29999999999999982236431605997495353221893310546875,
      ],
      35 => 
      [
        0 => 5.5,
        1 => 7.29999999999999982236431605997495353221893310546875,
        2 => 6.5,
        3 => 7.29999999999999982236431605997495353221893310546875,
      ],
      36 => 
      [
        0 => 6.0,
        1 => 7.29999999999999982236431605997495353221893310546875,
        2 => 6.70000000000000017763568394002504646778106689453125,
        3 => 7.29999999999999982236431605997495353221893310546875,
      ],
      37 => 
      [
        0 => 6.29999999999999982236431605997495353221893310546875,
        1 => 7.29999999999999982236431605997495353221893310546875,
        2 => 7.0,
        3 => 7.29999999999999982236431605997495353221893310546875,
      ],
      38 => 
      [
        0 => 6.5,
        1 => 7.29999999999999982236431605997495353221893310546875,
        2 => 7.29999999999999982236431605997495353221893310546875,
        3 => 7.29999999999999982236431605997495353221893310546875,
      ],
      39 => 
      [
        0 => 6.70000000000000017763568394002504646778106689453125,
        1 => 7.29999999999999982236431605997495353221893310546875,
        2 => 7.29999999999999982236431605997495353221893310546875,
        3 => 7.5,
      ],
      40 => 
      [
        0 => 7.0,
        1 => 7.5,
        2 => 7.29999999999999982236431605997495353221893310546875,
        3 => 8.0,
      ],
      41 => 
      [
        0 => 7.29999999999999982236431605997495353221893310546875,
        1 => 8.0,
        2 => 7.29999999999999982236431605997495353221893310546875,
        3 => 8.0,
      ],
      42 => 
      [
        0 => 7.29999999999999982236431605997495353221893310546875,
        1 => 8.0,
        2 => 7.5,
        3 => 8.0,
      ],
      43 => 
      [
        0 => 7.5,
        1 => 8.0,
        2 => 8.0,
        3 => 8.0,
      ],
      44 => 
      [
        0 => 8.0,
        1 => 8.0,
        2 => 8.0,
        3 => 8.0,
      ],
    ],
  ],
  'types' => 
  [
    0 => 
    [
      'key' => 1,
      'combo' => 'C',
      'archetype' => 'LOGICAL THINKER',
      'traits' => 
      [
        0 => 'Pendiam',
        1 => 'Anti Kritik',
        2 => 'Perfeksionis',
        3 => 'Cenderung Santai',
        4 => 'Detail',
        5 => 'Empati',
        6 => 'Rapi',
        7 => 'Organized',
        8 => 'Kaku pada Metode &amp; Prosedur',
      ],
      'jobs' => 'Planner (any function], Engineer (Installation, Technical], Technical/Research (Chemist Technician], Academic, Statistician, Government Worker, IT Management, Prison Officer, Quality Controller.',
      'desc' => 'Seorang yang praktis, cakap dan unik. Ia orang yang mampu menilai diri sendiri dan kritis terhadap dirinya dan orang lain. Ia menyukai hal yang detil dan logis; secara alamiah ia sangat analitis. Karena menyimpan informasi, ia meneliti isu berulang-ulang kali. Ia cenderung malu dan tertutup; ia hati-hati dalam membuat keputusan yang berdasarkan pada logika, bukan emosi, selalu menggunakan pertanyaan "bagaimana dan mengapa". Ia mengerjakan sesuatu dengan sistematis dan akurat. Ia rapi dan terorganisir sebab ia merasa bahwa keadaan berantakan sama dengan mutu yang rendah; demikian juga, rapi dan teratur merupakan mutu yang tinggi. Sangat teliti dalam segala sesuatu seperti halnya dalam pekerjaan dan penggunaan waktunya. Ia merencanakan dan mengorganisir semua sisi kehidupannya. Kelambanan sangat mengganggunya dan tak dapat ditolerir.',
    ],
    1 => 
    [
      'key' => 2,
      'combo' => 'D',
      'archetype' => 'ESTABLISHER',
      'traits' => 
      [
        0 => 'Individualis',
        1 => 'Ego Tinggi, Kurang Sensitif',
        2 => 'Kurang Pertimbangan',
        3 => 'Efektif',
        4 => 'High Motivation',
        5 => 'Bersemangat Tinggi',
        6 => 'Percaya Diri, cenderung Nekat',
        7 => 'Kreatif',
        8 => 'Terlalu Dominan',
        9 => 'Agresif',
        10 => 'Terlalu Dinamis',
      ],
      'jobs' => 'Attorney, Researcher, Sales Representative, Planning Consultant, Transport Personnel, Production (Director, Manager, Supervisor], Technologist, Strategic Planning, Trouble Shooting, Marketing Services, Consultant, Engineering (Director, Manager, Supervisor] and Self-Employment.',
      'desc' => 'Memiliki rasa ego yang tinggi dan cenderung invidualis dengan standard yang sangat tinggi. Ia lebih suka menganalisa masalah sendirian daripada bersama orang lain. Rasa egoisnya yang kuat membuatnya tidak nyaman di bawah kendali orang lain; ia lebih suka menjadi "boss" dan menetapkan standard tinggi baik untuk dirinya maupun orang lain. Ia menghindari sesuatu yang biasa-biasa dan cenderung mencari tantangan yang baru. Ia menyukai petualangan dan kadang-kadang beralih ke dalam petualangan baru sebelum mempertimbangkannya secara menyeluruh. Mampu memimpin situasi dan orang lain dalam rangka mencapai sasarannya; ia ingin selalu unggul dalam persaingan dengan taruhan apapun.',
    ],
    2 => 
    [
      'key' => 3,
      'combo' => 'D / C-D',
      'archetype' => 'DESIGNER',
      'traits' => 
      [
        0 => 'Sensitif',
        1 => 'Kurang Cepat',
        2 => 'Anti Tekanan ',
        3 => 'Terlalu Mandiri',
        4 => 'Kurang Percaya Orang Lain',
        5 => 'Anti Kritik',
        6 => 'Dingin',
        7 => 'Kreatif',
        8 => 'Result Oriented',
        9 => 'Suka Tantangan',
      ],
      'jobs' => 'Engineering (Management, Research, Design], Research (R&amp;D], Planning, Chemist, Accountancy, Specialist, Finance, Technician, Quality Control, Production Planning/Management, Design Engineer, Bookkeeper, Chemist Technician, Safety Officer, Librarian.',
      'desc' => 'Seorang yang sangat berorientasi pada tugas dan sensitif pada permasalahan. Ia lebih mempedulikan tugas yang ada dibanding orang-orang di sekitarnya, termasuk perasaan mereka. Sangat kukuh/keras dan mempunyai pendekatan yang efektif dalam pemecahan masalah. Oleh karena sifat alamiah dan keinginannya akan hasil yang terukur, Akan tampak dingin, tidak berperasaan dan menjaga jarak. Ia membuat keputusan berdasar pada fakta, bukan emosi. Cenderung pendiam dan tidak mudah percaya.',
    ],
    3 => 
    [
      'key' => 4,
      'combo' => 'D / I-D',
      'archetype' => 'NEGOTIATOR',
      'traits' => 
      [
        0 => 'Suka Bergaul',
        1 => 'Anti Rutin',
        2 => 'Aktif',
        3 => 'Terlalu Percaya Diri',
        4 => 'Agresif',
        5 => 'Optimis',
        6 => 'Kurang Detail',
        7 => 'Result Oriented',
      ],
      'jobs' => 'Sales and Marketing (Directing, Manager, Person], Public Relations, Recruitment Consultant, Politician, Director, Self-Employed, Hotelier, Travel Agent, Trainer, Hospitality, Lawyer, Solicitor, Motivators, Team Leader, Politician, Trainer, Lecturer, Theatrical Agent, General Management and Leading People, Attorney.',
      'desc' => 'Merupakan seorang pemimpin integratif yang bekerja dengan dan melalui orang lain.  Ia ramah, memiliki perhatian yang tinggi akan orang dan juga mempunyai kemampuan untuk memperoleh hormat dan penghargaan dari berbagai tipe orang.  Melakukan pekerjaannya dengan cara yang bersahabat, baik dalam mencapai sasarannya maupun meyakinkan pandangannya kepada orang lain.  Ia tidak begitu memperhatikan hal-hal kecil.  Kadang bertindak sesuai dengan kata hati/impulsif, terlalu antusias dan sangat banyak bicara.  Ia terlalu berlebihan menilai kemampuannya dalam memotivasi atau mengubah perilaku orang lain.  Mencari kebebasan dari rutinitas, menginginkan otoritas/wewenang dan juga prestise.  Ia menginginkan aktivitas yang bervariasi dan bekerja lebih efisien jika data-data analitis disediakan oleh orang lain.  Menginginkan penugasan yang mengutamakan mobilitas dan tantangan.',
    ],
    4 => 
    [
      'key' => 5,
      'combo' => 'D / I-D-C',
      'archetype' => 'CONFIDENT &amp; DETERMINED',
      'traits' => 
      [
        0 => 'Pandai Memilih Orang',
        1 => 'Leader',
        2 => 'Good Interpersonal Skill',
        3 => 'Dominan',
        4 => 'Agresif',
        5 => 'Perfeksionis',
        6 => 'Good Communication Skill',
        7 => 'Aktif',
        8 => 'Need Recognition n Reward',
        9 => 'Kurang Peduli pada Aturan',
        10 => 'Terburu-buru',
      ],
      'jobs' => 'Specialist/Technical Selling (Computer, Finance, Engineer and others, Chef, Technical/Capital Equipment Selling], Financial (Manager, Specialist], Computer Hardware Sales, Engineering (Manager, Designer, Buyer, Draughtsman], Project Engineer, Sales Engineer, Consultant, Trainer, Lecturer, Hotelier, Insurance, Mortgage and Finance Sales, Teacher, Travel Agent, Personnel and Marketing Services.',
      'desc' => 'Sangat berorientasi terhadap tugas dan juga menyukai orang.  Ia sangat baik dalam menarik orang/recruiting.  Seorang yang bersahabat, tetapi menyukai keadaan di mana tugas-tugas harus dilakukan dengan benar.  Ia kadang-kadang tampak dingin dan mendominasi.  Ia juga bisa sangat fokus pada tugas dan melupakan orang-orang di sekitarnya.  Sangat mengharapkan orang-orang terlibat dalam proyeknya, tetapi tidak memperdulikan apa yang diinginkan oleh orang-orang itu.  Ia perlu mendengar dan memikirkan  apa yang menjadi keinginan orang di sekitarnya, khususnya kesempatan untuk mencoba.  Ia sangat membutuhkan persetujuan sosial seperti halnya ia sangat mempercayai orang lain.  Karena itu, ia kadang-kadang berlebihan dalam menilai orang dan kemampuannya.  Ia tampak tidak konsisten dan tidak karuan karena ketidakmampuannya berkonsentrasi dan fokus dalam waktu yang lama.  Perlu belajar untuk secara sungguh-sungguh mendengarkan orang-orang di sekitarnya dari pada selalu berpikir apa yang ingin dikatakan.  Ia mempunyai kemampuan logika yang tinggi ketika ia mau menggunakannya.',
    ],
    5 => 
    [
      'key' => 6,
      'combo' => 'D / I-D-S',
      'archetype' => 'REFORMER',
      'traits' => 
      [
        0 => 'Mudah Bergaul',
        1 => 'Leader',
        2 => 'Sadar Diri',
        3 => 'Butuh Pujian &amp; Penghargaan',
        4 => 'Cepat Percaya Orang',
        5 => 'Mudah Simpati &amp; Empati',
        6 => 'Motivator',
        7 => 'Optimis &amp; Positif',
        8 => 'Anti Aturan',
        9 => 'Kurang Detail',
        10 => 'Terlalu Selektif',
      ],
      'jobs' => 'Hotelier, Customer Service, Complaints Manager, Recruiting Agent, Sales (Manager/Person], Marketing Services, Public Relations, Politician, Computer Software Sales, Lecturer, Engineering and Production (Manager/Supervisor].',
      'desc' => 'Seorang yang bersahabat dan sosial; ia juga suka mengendalikan situasi dan menjadi pemimpin.  Ia menyelesaikan tugasnya melalui keterampilan sosialnya; ia peduli dan menerima orang lain.  Ia berkonsentrasi pada tugas yang ada di tangannya sampai selesai dan akan minta bantuan orang lain jika perlu.  Ia menyadari keterbatasannya dan meminta bantuan jika memerlukannya.  Ia disukai dan orang ingin menolongnya.  Senang membagi kebanggaannya dengan kelompok; ia seorang team player tetapi juga team leader.  Menginginkan popularitas dan pengakuan.',
    ],
    6 => 
    [
      'key' => 7,
      'combo' => 'D / I-S-D',
      'archetype' => 'MOTIVATOR',
      'traits' => 
      [
        0 => 'Leader (Kelompok Kecil]',
        1 => 'Supporter',
        2 => 'Sosialisasi Baik',
        3 => 'Butuh Ketegasan',
        4 => 'Butuh Pujian &amp; Penghargaan',
        5 => 'Kurang Detail',
        6 => 'Agak Kaku',
      ],
      'jobs' => 'Hotelier, Community Counseling, Customer Service, Complaints Manager, Community Work, Recruitment Consultant, Hospitality, Teacher, Telemarketing, Production Manager, Complaints Manager, Recruiting Agent, Sales (Manager/Person], Marketing Services, Public Relations, Politician, Call Centre Manager, Lecturer, Engineering and Production (Manager/Supervisor].',
      'desc' => 'Seorang yang menampilkan gaya bersemangat ketika termotivasi pada sasaran.  Ia lebih suka memimpin atau melibatkan diri, walaupun ia juga mau melayani sebagai pembantu.  Ia membutuhkan pengakuan dan penghargaan serta senang pada peran pendukung.  Ia peduli kepada orang-orang di sekitarnya dan akan mempertimbangkan perasaan orang lain dalam proses pengambilan keputusan.  Menampilkan keterampilan berhubungan dan berkomunikasi dengan sangat baik.  Ia akan berusaha keras menyelesaikan tugas dengan cepat dan efisien.',
    ],
    7 => 
    [
      'key' => 8,
      'combo' => 'D / S-D-C / S-C-D',
      'archetype' => 'INQUIRER',
      'traits' => 
      [
        0 => 'Full Self Control',
        1 => 'Sabar',
        2 => 'Penuh Pertimbangan',
        3 => 'Good Interpersonal',
        4 => 'Selektif',
        5 => 'Lambat Adaptasi',
        6 => 'Inisiatif kurang',
        7 => 'Result Oriented',
        8 => 'Kaku dan Keras Kepala',
        9 => 'Good Service',
        10 => 'Kurang dlm hal Managerial',
      ],
      'jobs' => 'Directing, Managing or Supervising (in Engineering, Accountancy, Research and Development and Computing disciplines], Research Manager, Scientific Work, Accountant, Administration, Project Engineer, Draughtsman, Designer, Analyst, Finance, Chemist, Technical Service Support, Flight Attendant, Technician, Service Engineer, Service Manager, Security Specialist.',
      'desc' => 'Seorang yang sabar, terkontrol dan suka menggali fakta dan jalan keluar.  Ia tenang dan ramah.  Ia merencanakan pekerjaan dengan hati-hati, tetapi agresif, menanyakan sesuatu serta mengumpulkan data pendukung.  Kemudian ia bekerja dengan konsisten dengan arahan yang benar.  Menjadi individu yang penuh perhatian, rendah hati, dan ia berhubungan baik dengan hampir semua orang.  Seorang yang konsisten dan suka menolong. People skill darinya melebihi orientasi tugasnya.',
    ],
    8 => 
    [
      'key' => 9,
      'combo' => 'D-I',
      'archetype' => 'PENGAMBIL KEPUTUSAN',
      'traits' => 
      [
        0 => 'Pekerja Keras',
        1 => 'Leader',
        2 => 'Banyak Minat',
        3 => 'Dingin / Task Oriented',
        4 => 'Kurang Pergaulan',
        5 => 'Kontrol Emosi Kurang',
        6 => 'Suka Tantangan',
        7 => 'Cepat Bosan',
        8 => 'Anti Aturan',
        9 => 'Kurang Detail',
        10 => 'Kurang Peduli Wewenang',
      ],
      'jobs' => 'General Management (Directing/Managing/Supervising, Public Relations, Business Management, Conflict Resolution, Industrial Relations, Business Consultant, Trouble Shooting, Sales and Sales Management, Marketing, Promoting, Production (Director, Manager, Supervisor], Consultancy, Publishing, Sales Executive, Promotional Work, Brokers, Self-Employment, Advertising, Lecturing, Dealing/Broking.',
      'desc' => 'Tidak basa-basi dan tegas, ia cenderung merupakan seorang invidualis yang kuat. Ia berpandangan jauh ke depan, progresif dan mau berkompetisi untuk mencapai sasaran. DI seorang yang selalu ingin tahu dan mempunyai minat dengan cakupan yang luas. Ia seorang yang logis, kritis dan tajam dalam memecahkan masalah. Sering kali ia tampak imajinatif. Ia mempunyai kemampuan memimpinan yang baik. Ia kadang tampak keras kepala atau dingin karena orientasi dan prioritasnya pada tugas cenderung melebihi orientasi terhadap sesama. Ia mencanangkan standard tinggi pada dirinya dan akan sangat kritis ketika standard ini tidak dicapai. Ia juga menempatkan standard tinggi pada orang-orang di sekitarnya, serta mengutamakan kesempurnaan. Ia menginginkan otoritas yang jelas dan menyukai tugas-tugas baru.',
    ],
    9 => 
    [
      'key' => 10,
      'combo' => 'D-I-S',
      'archetype' => 'DIRECTOR',
      'traits' => 
      [
        0 => 'Pengelola',
        1 => 'Enerjik',
        2 => 'Kurang Detail',
        3 => 'Mudah Bosan',
        4 => 'Agresif',
        5 => 'Arogan',
        6 => 'Kurang Focus',
      ],
      'jobs' => 'Engineering and Production (Directing, Managing, Supervising], Sales, Sales Management, Service Manager, Distribution, Public Relations, Office Management, Account Manager, Customer Service, Retail Manager, IT, Lecturer, Logistics, Manager-General, National Accounts Manager, Teacher, Projects Manager.',
      'desc' => 'Fokus pada penyelesaian pekerjaan dan menunjukkan penghargaan yang tinggi kepada orang lain.  Ia memiliki kemampuan untuk menggerakkan orang dan pekerjaan dikarenakan keterampilannya berpikir ke depan dan hubungan antar manusia.  Tidak berorientasi detil, ia fokus pada target secara keseluruhan dengan menyerahkan hal detil kepada orang lain.  Enerjik dan sosial, ia mampu memotivasi orang lain sambil menyelesaikan pekerjaannya.  Ia menampilkan rasa percaya diri dan mampu meyakinkan orang lain.  Sekali ia memutuskan sesuatu, ia akan terus mengerjakannya dan bertahan sampai selesai.',
    ],
    10 => 
    [
      'key' => 11,
      'combo' => 'D-S ',
      'archetype' => 'SELF-MOTIVATED',
      'traits' => 
      [
        0 => 'Objektif &amp; Analitis',
        1 => 'Mandiri',
        2 => 'Good Planner',
        3 => 'Komitmen thd Target',
        4 => 'Menghindari Konflik',
      ],
      'jobs' => 'Engineering and Production (Directing, Managing, Supervising], Project Management, Researcher, Chemist (R&amp;D], Planner, Engineering (R&amp;D], Systems Analyst, Commercial Planner, Computer Engineer, Programmer, IT, Other computer-related disciplines, Technical Trouble Shooting and Directing, Lawyer, Solicitor, Development Engineer, Work Study, Barrister, Attorney.',
      'desc' => 'Seorang yang obyektif dan analitis.  Ia ingin terlibat dalam situasi, dan ia juga ingin memberikan bantuan dan dukungan kepada orang yang ia hormati.  Secara internal termotivasi oleh target pribadi, ia berorientasi terhadap pekerjaannya tapi juga menyukai hubungan dengan sesama.  Karena determinasinya yang kuat, ia sering berhasil dalam berbagai hal; karakternya yang tenang, stabil dan daya tahannya yang tinggi memiliki kontribusi dalam keberhasilannya.  Ulet dalam memulai pekerjaan. Ia akan berusaha keras untuk mencapai sasarannya.  Seorang yang mandiri dan cermat serta memiliki tindak lanjut yang baik.',
    ],
    11 => 
    [
      'key' => 12,
      'combo' => 'I / C-I-S',
      'archetype' => 'MEDIATOR',
      'traits' => 
      [
        0 => 'Loyal',
        1 => 'Tight Scheduled',
        2 => 'Curious',
        3 => 'Sensitif',
        4 => 'Good Communication Skill',
        5 => 'Good Analitical Think',
        6 => 'Good Interpersonal Skill',
        7 => 'Cepat Beradaptasi',
        8 => 'Anti Kritik',
        9 => 'Not Leader',
        10 => 'Work/Play Conflict',
      ],
      'jobs' => 'Engineering and Production (Supervisor, Installer, Technician, Service and Design], Research (Supervisor, Chemist, Lab. Technician], Trainer, Finance (Supervisor, Accountant, Advisor], Public Relations, Administration, Office Administrator, Market Analyst, System Analyst, Programmer, Selling (Technical/Service].',
      'desc' => 'Merupakan individu yang berorientasi pada orang, ia mampu menggabungkan ketepatan dan loyalitas.  Ia cenderung peka dan mempunyai standard yang tinggi.  Ia menginginkan stabilitas dan berorientasi terhadap sasaran.  Ia menginginkan pengakuan sosial dan perhatian pribadi.  Ia bersahabat, antusias, informal, banyak bicara, dan mungkin sangat mencemaskan apa yang dipikirkan oleh orang lain.  Ia menolak agresi, dan mengharapkan suasana harmonis.  Ia cenderung cukup cerdas dalam berbagai hal. Ia merupakan pencari fakta yang sangat baik dan akan membuat keputusan yang baik setelah mengumpulkan fakta dan data pendukung.',
    ],
    12 => 
    [
      'key' => 13,
      'combo' => 'I / C-S-I',
      'archetype' => 'PRACTITIONER',
      'traits' => 
      [
        0 => 'Perfeksionis',
        1 => 'Quality Oriented',
        2 => 'Scheduled',
        3 => 'Anti Kejutan',
        4 => 'Good Interpersonal Skill',
        5 => 'Terlalu Detail',
        6 => 'Sistematis',
        7 => 'Kaku / Tidak fleksibel',
        8 => 'Monoton',
      ],
      'jobs' => 'Engineering and Production (Supervisor, Installer, Technician, Service and Design], Research (Supervisor, Chemist], Trainer, Finance (Manager, Supervisor, Accountant, Advisor], Public Relations-Administration, Purchasing, Chemist Research, Office Administrator, Computer Programmer, Market Analyst, System Analyst, Programmer, Research and Development Supervisor, Laboratory Technician, Legal, Selling (Technical/Service].',
      'desc' => 'Merupakan individu yang berorientasi pada orang, ia mampu menggabungkan ketepatan dan loyalitas.  Ia cenderung peka dan mempunyai standard yang tinggi.  Ia menginginkan stabilitas dan berorientasi terhadap sasaran.  Ia menginginkan pengakuan sosial dan perhatian pribadi.  Bersahabat, antusias, informal, banyak bicara, dan mungkin sangat mencemaskan apa yang dipikirkan oleh orang lain.  Ia menolak agresi dan mengharapkan suasana harmonis.  Ia cenderung cukup cerdas dalam berbagai hal. Ia merupakan pencari fakta yang sangat baik dan akan membuat keputusan yang baik setelah mengumpulkan fakta dan data pendukung.',
    ],
    13 => 
    [
      'key' => 14,
      'combo' => 'I-S-C / I-C-S',
      'archetype' => 'RESPONSIVE &amp; THOUGHTFUL',
      'traits' => 
      [
        0 => 'High Energy',
        1 => 'Good Communication Skill',
        2 => 'To The Point',
        3 => 'Sensitif',
        4 => 'Banyak Bicara',
        5 => 'Need Recognation',
        6 => 'Need Socialism',
        7 => 'Anti thd Kritik',
        8 => 'Terlalu banyak bersosialisasi',
        9 => 'Leadership kurang',
        10 => 'Kurang Fokus',
      ],
      'jobs' => 'Actors, Chef, Personnel, Welfare, Broadcasting, Training, Attorney, Teaching, Accounting, Technical Instructor, Accounting-General, Accounts Supervisor, Customer Services, Public Relations, Artist, Hotelier, Demonstrator, Florist/Floral Designer, Engineering (Sales, Service, Project, Draughtsman, Designer], Graphic Designer, Specialist (Soft/Services], Selling, Purchasing, Singers, Technical Instructor, Personnel Management, Politician, Supervising (Engineering, Production, Accounts], Administration Work, Sales Engineer, Secretarial, Industrial Relations Specialist.',
      'desc' => 'Merupakan individu yang berorientasi pada orang dan lancar berkomunikasi serta loyal.  Ia cenderung sensitif dan mempunyai standard yang tinggi.  Keputusannya dibuat berdasarkan fakta dan data pendukung.  Ia sepertinya tidak bisa diam.  Ia perlu untuk lebih terus terang dan jangan terlalu subyektif.  Ia butuh pengakuan sosial dan perhatian pribadi; ia dapat cepat akrab dengan orang lain.  Ia bersahabat, antusias, informal, banyak bicara dan terlalu khawatir terhadap apa yang dipikirkan orang.  Ia menguasai banyak hal.  Ia ingin diterima sebagai anggota kelompok dan ingin mengetahui secara pasti apa yang diharapkan darinya sebelum ia memulai proyek baru.',
    ],
    14 => 
    [
      'key' => 15,
      'combo' => 'S',
      'archetype' => 'SPECIALIST',
      'traits' => 
      [
        0 => 'Stabil &amp; Konsisten',
        1 => 'Terkendali',
        2 => 'Nyaman di Belakang Layar',
        3 => 'Sabar',
        4 => 'Loyal',
        5 => 'Sulit Adaptasi',
        6 => 'Process Oriented',
        7 => 'Teguh',
        8 => 'Need for Peace',
        9 => 'Anti Perubahan',
        10 => 'Sulit Menentukan Prioritas',
      ],
      'jobs' => 'Administrative Work, Engineering and Production areas (Sales, Services, Project, Painter, Plumber, Draughtsman, Designer, Operative], Chef, Accounting, Telemarketing/Tele-Sales, Research and Development, Administrator, Florist/Floral Designer, Retail-General, Sales-General, Accounting-General, Service-General, Landscape Gardener.',
      'desc' => 'Merupakan individu konsisten yang berusaha menjaga lingkungan/suasana yang tidak berubah.  Ia bekerja dengan baik bersama orang-orang dengan berbagai kepribadian karena perilakunya yang terkendali dan rendah hati.  Sabar, loyal dan suka menolong.  Persahabatan dikembangkannya dengan lambat dan selektif.  Ia tidak bosan dengan rutinitas dan sangat baik bekerja dengan petunjuk dan peraturan yang jelas. Ia mengharapkan bantuan dan supervisi pada saat mengawali proyek baru.  Ia butuh waktu untuk menyesuaikan diri dengan perubahan dan sungkan menjalankan "cara-cara lama mengerjakan sesuatu".  Ia akan menghindari konfrontasi dan berusaha sekuat tenaga memendam perasaannya.',
    ],
    15 => 
    [
      'key' => 16,
      'combo' => 'S / C-S',
      'archetype' => 'PERFECTIONIST',
      'traits' => 
      [
        0 => 'Detail &amp; Teliti',
        1 => 'Butuh Situasi Stabil',
        2 => 'Sistematik &amp; Prosedural',
        3 => 'Menghindari Konflik',
        4 => 'Anti Kritik',
        5 => 'Lambat Memutuskan',
        6 => 'Sulit Adaptasi',
        7 => 'Pendendam',
        8 => 'Anti Perubahan',
      ],
      'jobs' => 'Researcher (Technician, Chemist, Quality Control], Engineer (Project, Draughtsman, Armed Forces, Designer], Statistician, Surveyor, Optician, Medical Specialist, Health Care, IT Management, Planner, Technical Writing, Production, Dentist, Quality Control, Planning, Dental Technician, Accounting, Computer Programmer, Psychologist, Surgeon, Architect, Medical Specialist.',
      'desc' => 'Berpikir sistematis dan cenderung mengikuti prosedur dalam kehidupan pribadi dan pekerjaannya.  Teratur dan memiliki perencanaan yang baik, ia teliti dan fokus pada detil.  Bertindak dengan penuh kebijaksanaan, diplomatis dan jarang menentang rekan kerjanya dengan sengaja.  Ia sangat berhati-hati, sungguh-sungguh mengharapkan akurasi dan standard tinggi dalam pekerjaannya.  Ia cenderung terjebak dalam hal detil, khususnya jika harus memutuskan.  Menginginkan adanya petunjuk standard pelaksanaan kerja dan tanpa perubahan mendadak.',
    ],
    16 => 
    [
      'key' => 17,
      'combo' => 'S-C',
      'archetype' => 'PEACEMAKER, RESPECTFULL &amp; ACCURATE',
      'traits' => 
      [
        0 => 'Sulit Beradaptasi',
        1 => 'Anti Kritik',
        2 => 'Pendendam',
        3 => 'Sukar Berubah',
        4 => 'Detail',
        5 => 'Empati',
        6 => 'Memikirkan Dampak ke Orang Lain',
        7 => 'Terlalu Mendalam dalam Berpikir',
        8 => 'Concern ke Data dan Fakta',
        9 => 'Introvert',
        10 => 'Loyal',
      ],
      'jobs' => 'Office (Manager, Supervisor, Person], Chief Clerk, General Administrator, Production Supervisor, Planner, Accountant, Research and Development, Flight Attendant, Engineering (Project Manager, Supervisor, Technician], Computer Programmer, Draughtsman, Soft/Service Selling, Doctor, Cashier, Receptionist, Data Entry, Planner, Word Processing, Property Manager, Database Administrator, Health Care, Statistician, Nursing-Administration, Company Secretary, System Analyst, Programmer, Statistician, Accounting-General, Security Specialist.',
      'desc' => 'Ia adalah orang yang baik secara alamiah dan sangat berorientasi detil.  Ia peduli dengan orang-orang di sekitarnya dan mempunyai kualitas yang membuatnya sangat teliti dalam penyelesaian tugas.  Ia mempertimbangkan sekelilingnya dengan hati-hati sebelum membuat keputusan untuk melihat pengaruhnya pada mereka; saat tertentu ia terlalu hati-hati.  Jika ia merasa seseorang memanfaatkan situasi, ia akan memperlambat kerjanya sehingga dapat mengamati apa yang sedang berlangsung di sekitarnya.',
    ],
    17 => 
    [
      'key' => 18,
      'combo' => 'D-C',
      'archetype' => 'CHALLENGER',
      'traits' => 
      [
        0 => 'Seorang yang tekun',
        1 => 'Sensitif terhadap permasalahan',
        2 => 'Mempunyai keputusan yang kuat',
        3 => 'Kreatif  dalam memecahkan masalah',
        4 => 'Memiliki reaksi yang cepat',
        5 => 'Mampu mencari solusi permasalahan',
        6 => 'Banyak memberikan ide-ide.',
        7 => 'Usaha yang keras pada ketepatan',
        8 => 'Cenderung perfeksionis',
      ],
      'jobs' => 'Engineering (Management, Research, Design], Actuaries, Research (R&amp;D], Planning, Chemist, Hospital Supervisor, Industrial Marketing, Investment Banking, Medical Administrator, Mortgage Brokers, Accountancy, Fund Management, Specialist Finance, Quality Control and Specialist work in any area where knowledge and experience is available, Production, Financial Services, Technical Management, Project Leader, Matron, Strategic Planning, Industrial Marketing.',
      'desc' => 'Seorang yang sensitif terhadap permasalahan, dan memiliki kreativitas yang baik dalam memecahkan masalah. Ia dapat menyelesaikan tugas-tugas penting dalam waktu singkat karena mempunyai keputusan yang kuat. Seorang yang tekun dan memiliki reaksi yang cepat.  Ia akan meneliti dan mengejar semua kemungkinan yang ada dalam mencari solusi permasalahan.  Ia banyak memberikan ide-ide dengan berfokus pada pekerjaan. Usaha yang keras pada ketepatan akan mengimbangi keinginannya pada hasil yang terukur.  Ia cenderung perfeksionis dan dapat juga memperlambat pengambilan keputusan karena keinginannya untuk menentukan pilihan yang terbaik.',
    ],
    18 => 
    [
      'key' => 19,
      'combo' => 'D-I-C',
      'archetype' => 'CHANCELLOR',
      'traits' => 
      [
        0 => 'Seorang yang ramah secara alami',
        1 => 'Menggabungkan kesenangan dengan pekerjaan',
        2 => 'Menyukai hubungan dengan sesama',
        3 => 'Menikmati interaksi dengan sesama',
        4 => 'Dapat mengerjakan hal-hal detil',
        5 => 'Ingin melakukan segala sesuatu dengan tepat',
        6 => 'Menilai orang dan tugas secara hati-hati',
        7 => 'Sering melalaikan perencanaan yang seksama',
        8 => 'Mudah beralih kepada proyek-proyek baru',
      ],
      'jobs' => 'Technical/Scientific (Directing, Management, Supervision], Engineering, Finance, Production Planning, Personnel Disciplines, Self-Employment, Credit Manager, Planner, Fund Management, Computer Hardware/Software Sales, IT, Business Consultant, Banking, Logistics, Lecturing, Work Study, Film Director, Transport, Consultancy, Industrial Relations and Computers (Selling, Software, Systems Analyst] and General Manager.',
      'desc' => 'Ia menggabungkan antara kesenangan dengan pekerjaan/bisnis ketika melakukan sesuatu. Ia kelihatan menyukai hubungan dengan sesama tetapi juga dapat mengerjakan hal-hal detil. Ia ingin melakukan segala sesuatu dengan tepat, dan ia akan menyelesaikan tugasnya untuk meyakinkan ketepatan dan kelengkapannya. Seorang yang ramah secara alami dan menikmati interaksi dengan sesama, akan tetapi ia akan juga menilai orang dan tugas secara hati-hati; persahabatannya akan bergeser sesuai dengan dorongan hatinya pada orang lain di sekitarnya. Ia sering melalaikan perencanaan yang seksama dan akan beralih ke pada proyek-proyek baru tanpa pertimbangan yang menyeluruh.',
    ],
    19 => 
    [
      'key' => 20,
      'combo' => 'D-S-I',
      'archetype' => 'DIRECTOR',
      'traits' => 
      [
        0 => 'Seorang yang obyektif dan analitis',
        1 => 'Ingin terlibat dalam situasi',
        2 => 'Ingin memberikan bantuan dan dukungan',
        3 => 'Termotivasi oleh target pribadi',
        4 => 'Berorientasi terhadap pekerjaannya',
        5 => 'Menyukai hubungan dengan sesama',
        6 => 'Mempunyai determinasi yang kuat',
        7 => 'Karakternya tenang',
        8 => 'Stabil dan daya tahannya tinggi',
        9 => 'Ulet dalam memulai pekerjaan',
        10 => 'Berusaha keras mencapai sasarannya',
      ],
      'jobs' => 'Engineering and Production (Directing, Managing, Supervising], Sales, Sales Management, Service Manager, Distribution, Public Relations, Creative Designer, Office Management, Chief Engineer, Business Consultant, Chief Financial Officer, Customer Service, National Accounts Manager, Chief Accountant, Lecturer, Projects Manager, Research Planning, Human Resources, Scientific Work, Security Specialist, Solicitor, Planner, Production Administrator.',
      'desc' => 'Seorang yang obyektif dan analitis.  Ia ingin terlibat dalam situasi, dan ia juga ingin memberikan bantuan dan dukungan kepada orang yang ia hormati.  Secara internal termotivasi oleh target pribadi, ia berorientasi terhadap pekerjaannya tapi juga menyukai hubungan dengan sesama.  Karena determinasinya yang kuat, ia sering berhasil dalam berbagai hal; karakternya yang tenang, stabil dan daya tahannya yang tinggi memiliki kontribusi dalam keberhasilannya.  Ulet dalam memulai pekerjaan. Ia akan berusaha keras untuk mencapai sasarannya.  Seorang yang mandiri dan cermat serta memiliki tindak lanjut yang baik.',
    ],
    20 => 
    [
      'key' => 21,
      'combo' => 'D-S-C',
      'archetype' => 'Director',
      'traits' => 
      [
        0 => 'Seorang yang obyektif dan analitis',
        1 => 'Ingin terlibat dalam situasi',
        2 => 'Ingin memberikan bantuan dan dukungan',
        3 => 'Termotivasi oleh target pribadi',
        4 => 'Berorientasi terhadap pekerjaannya',
        5 => 'Menyukai hubungan dengan sesama',
        6 => 'Mempunyai determinasi yang kuat',
        7 => 'Karakternya tenang',
        8 => 'Stabil dan daya tahannya tinggi',
        9 => 'Ulet dalam memulai pekerjaan',
        10 => 'Berusaha keras mencapai sasarannya',
      ],
      'jobs' => 'Engineering and Production (Directing, Managing, Supervising], Sales, Sales Management, Service Manager, Distribution, Public Relations, Creative Designer, Office Management, Chief Engineer, Business Consultant, Chief Financial Officer, Customer Service, National Accounts Manager, Chief Accountant, Lecturer, Projects Manager, Research Planning, Human Resources, Scientific Work, Security Specialist, Solicitor, Planner, Production Administrator.',
      'desc' => 'Seorang yang obyektif dan analitis.  Ia ingin terlibat dalam situasi, dan ia juga ingin memberikan bantuan dan dukungan kepada orang yang ia hormati.  Secara internal termotivasi oleh target pribadi, ia berorientasi terhadap pekerjaannya tapi juga menyukai hubungan dengan sesama.  Karena determinasinya yang kuat, ia sering berhasil dalam berbagai hal; karakternya yang tenang, stabil dan daya tahannya yang tinggi memiliki kontribusi dalam keberhasilannya.  Ulet dalam memulai pekerjaan. Ia akan berusaha keras untuk mencapai sasarannya.  Seorang yang mandiri dan cermat serta memiliki tindak lanjut yang baik.',
    ],
    21 => 
    [
      'key' => 22,
      'combo' => 'D-C-I',
      'archetype' => 'CHALLENGER',
      'traits' => 
      [
        0 => 'Seorang yang tekun',
        1 => 'Sensitif terhadap permasalahan',
        2 => 'Mempunyai keputusan yang kuat',
        3 => 'Kreatif  dalam memecahkan masalah',
        4 => 'Memiliki reaksi yang cepat',
        5 => 'Mampu mencari solusi permasalahan',
        6 => 'Banyak memberikan ide-ide.',
        7 => 'Usaha yang keras pada ketepatan',
        8 => 'Cenderung perfeksionis',
      ],
      'jobs' => 'Technical/Scientific (Directing, Management, Supervision], Engineering, Finance, Production Planning, Personnel Disciplines, Self-Employment, Credit Manager, Planner, Lecturing, Work Study, Transport, Consultancy, Industrial Relations and Computers (Selling, Software, Systems Analyst] and General Manager.',
      'desc' => 'Seorang yang sensitif terhadap permasalahan, dan memiliki kreativitas yang baik dalam memecahkan masalah. Ia dapat menyelesaikan tugas-tugas penting dalam waktu singkat karena mempunyai keputusan yang kuat. Seorang yang tekun dan memiliki reaksi yang cepat.  Ia akan meneliti dan mengejar semua kemungkinan yang ada dalam mencari solusi permasalahan.  Ia banyak memberikan ide-ide dengan berfokus pada pekerjaan. Usaha yang keras pada ketepatan akan mengimbangi keinginannya pada hasil yang terukur.  Ia cenderung perfeksionis dan dapat juga memperlambat pengambilan keputusan karena keinginannya untuk menentukan pilihan yang terbaik.',
    ],
    22 => 
    [
      'key' => 23,
      'combo' => 'D-C-S',
      'archetype' => 'CHALLENGER',
      'traits' => 
      [
        0 => 'Seorang yang tekun',
        1 => 'Sensitif terhadap permasalahan',
        2 => 'Mempunyai keputusan yang kuat',
        3 => 'Kreatif  dalam memecahkan masalah',
        4 => 'Memiliki reaksi yang cepat',
        5 => 'Mampu mencari solusi permasalahan',
        6 => 'Banyak memberikan ide-ide.',
        7 => 'Usaha yang keras pada ketepatan',
        8 => 'Cenderung perfeksionis',
      ],
      'jobs' => 'Engineering, Production and Finance (Directing, Administrating, Managing and Managing Specialist Work], Scientific, Research Planning, Personnel, Trouble Shooting, Credit Control, Chief Accountant, Accountant, Chief Engineer, Work Study, Consultancy, Designer, Draughtsman, Project Work, Security Specialist, Doctor, Attorney.',
      'desc' => 'Seorang yang sensitif terhadap permasalahan, dan memiliki kreativitas yang baik dalam memecahkan masalah. Ia dapat menyelesaikan tugas-tugas penting dalam waktu singkat karena mempunyai keputusan yang kuat. Seorang yang tekun dan memiliki reaksi yang cepat.  Ia akan meneliti dan mengejar semua kemungkinan yang ada dalam mencari solusi permasalahan.  Ia banyak memberikan ide-ide dengan berfokus pada pekerjaan. Usaha yang keras pada ketepatan akan mengimbangi keinginannya pada hasil yang terukur.  Ia cenderung perfeksionis dan dapat juga memperlambat pengambilan keputusan karena keinginannya untuk menentukan pilihan yang terbaik.',
    ],
    23 => 
    [
      'key' => 24,
      'combo' => 'I',
      'archetype' => 'COMMUNICATOR',
      'traits' => 
      [
        0 => 'Antusias',
        1 => 'Percaya',
        2 => 'Optimis',
        3 => 'Persuasif',
        4 => 'Bicara aktif',
        5 => 'Impulsif',
        6 => 'Emosional',
        7 => 'Ramah',
        8 => 'Inspirasional',
      ],
      'jobs' => 'Promoting, Demonstrating, Canvassing, Marketing Services, Public Relations, Lecturing, Advertising, Publican, Publishing, Hospitality, Retail-General, Human Resources, Journalist, Singers, Technical Writing, Tour Guide, Promotional Work, Hotelier, Dancers, Host, Actors, Travel Agent, Politician, and very soft selling.',
      'desc' => 'Merupakan seorang yang antusias dan optimistik, ia lebih suka mencapai sasarannya melalui orang lain. Ia suka berhubungan dengan sesamanya - ia bahkan suka mengadakan “pesta” atau kegiatan untuk berkumpul, dan ini menunjukkan kepribadiannya yang ramah. Ia tidak suka bekerja sendirian dan cenderung bersama dengan orang lain dalam menyelesaikan proyek.  Perhatian dan fokusnya tidak sebaik apa yang dia inginkan -  maka ia membutuhkan energi yang besar untuk mampu bergerak cepat dari satu hal ke hal berikutnya tanpa penundaan.  Ia sangat menonjol dalam keterampilan berkomunikasi, dan ini merupakan salah satu kekuatan yang paling sering digunakan.  Ia memiliki kemampuan untuk memotivasi dan memberi semangat dengan kata-katanya, dan ia dikenal sebagai individu yang inspirasional. Ketika ia harus memusatkan perhatiannya pada tugas, Ia akan menjadi tidak akurat dan bahkan tidak terorganisir.  Tetapi ia akan memusatkan perhatian kepada yang harus ia senangkan, karena ia enggan sekali untuk menolak.  Ia menginginkan pengakuan sosial dan takut akan penolakan.  Ia mudah menemukan teman dan berusaha menciptakan suasana yang menyenangkan.  Ia membutuhkan seorang manajer atau supervisor untuk menentukan batas waktu yang jelas dalam pekerjaannya, ia lebih suka menggunakan gaya manajemen partisipatif yang dibangun berdasarkan hubungan yang kuat.',
    ],
    24 => 
    [
      'key' => 25,
      'combo' => 'I-S',
      'archetype' => 'ADVISOR',
      'traits' => 
      [
        0 => 'Hangat',
        1 => 'Simpati',
        2 => 'Tenang dalam situasi sosial',
        3 => 'Pendengar yang baik',
        4 => 'Demonstratif',
        5 => 'Tidak memaksakan idenya pada orang lain',
        6 => 'Kurang tegas dalam memberi perintah',
        7 => 'Menerima kritik',
        8 => 'Toleran dan sabar',
        9 => 'Penjaga damai',
      ],
      'jobs' => 'Personnel, Welfare, Training, Hotelier, Promoting, Travel Agent, Lecturing, Upmarket/Speciality Sales, Soft/Service Selling, Beauty Therapist, Psychologist, Nursing, Human Resources, Retail-Specialist, Veterinarian, Social Work, Personal Assistant, Personnel-HR, Coach, Mentor.',
      'desc' => 'Seorang yang mengesankan orang akan kehangatan, simpati dan pengertiannya.  Ia memiliki ketenangan dalam sebagian besar situasi sosial dan jarang tidak menyenangkan orang lain.  Faktanya, banyak orang datang padanya karena ia kelihatan sebagai pendengar yang baik.  Ia cenderung sangat demonstratif dan emosinya biasanya tampak jelas bagi orang di sekitarnya.  Ia tidak akan memaksakan idenya pada orang lain; ia tidak tegas dalam mengekspresikan atau memberi perintah.  Jika ia sangat kuat merasakan sesuatu, Ia akan bicara secara terbuka dan terus terang tentang pendiriannya.  Ia cenderung menerima kritik atas pekerjaannya sebagai serangan pribadi.  Ia dapat menjadi sangat toleran dan sabar kepada mereka yang tidak produktif di pekerjaan.  Ia merupakan "penjaga damai" dan akan bekerja untuk menjaga kedamaian dalam setiap keadaan.',
    ],
    25 => 
    [
      'key' => 26,
      'combo' => 'I-C',
      'archetype' => 'ASSESSOR',
      'traits' => 
      [
        0 => 'Ramah',
        1 => 'Suka berteman',
        2 => 'Nyaman walapun dengan orang asing',
        3 => 'Mudah mengembangkan hubungan baru',
        4 => 'Dapat mengendalikan diri',
        5 => 'Sangat sosial',
        6 => 'Cenderung perfeksionis alamiah',
        7 => 'Mempromosikan tugas-tugas orang lain',
      ],
      'jobs' => 'Teaching, Training, Inventing, Specialist Selling (Engineering, Finance or any area involving capital equipment], Project Engineer, Finance, Service Engineer or Supervising within a Technical/Specialist Area, Public Relations, Environmentalist, Marketing, Conference Organiser, Estate Agent.',
      'desc' => 'Merupakan seorang yang ramah dan suka berteman; ia merasa nyaman walaupun dengan orang asing. Ia dapat mengembangkan hubungan baru dengan mudah, dan pada umumnya dapat mengendalikan diri sampai pada tingkat dimana ia jarang menimbulkan rasa benci pada orang lain dengan sengaja. Ia seorang yang sangat sosial, menunjukkan kepedulian dan persahabatan ketika sedang melakukan tugas-tugas di tangannya. Ia cenderung perfeksionis secara alamiah, dan akan mengisolasi dirinya jika diperlukan untuk melaksanakan pekerjaan.  Ia berkeinginan mempromosikan tugas-tugas orang lain, juga kepunyaannya.  Kadang-kadang ia salah menilai kemampuan orang lain dikarenakan pandangan-pandangannya yang optimis.',
    ],
    26 => 
    [
      'key' => 27,
      'combo' => 'I-C-D',
      'archetype' => 'ASSESSOR',
      'traits' => 
      [
        0 => 'Analitis',
        1 => 'Berwatak hati-hati',
        2 => 'Ramah pada saat merasa nyaman',
        3 => 'Sangat biasa dengan orang asing',
        4 => 'Mudah mengembangkan hubungan baru',
        5 => 'Dapat mengendalikan diri',
        6 => 'Peduli dan ramah',
        7 => 'Memusatkan perhatian pada penyelesaian tugas',
        8 => 'Perfeksionis secara alami',
        9 => 'Mengisolasi dirinya jika diperlukan',
        10 => 'Mudah diramalkan',
      ],
      'jobs' => 'Specialist/Technical Selling (Computer, Finance, Engineer and others, Technical/Capital Equipment Selling], Financial (Manager, Specialist], Engineering (Manager, Designer, Buyer, Draughtsman], Project Engineer, Sales Engineer, Consultant, Trainer, Lecturer, Hotelier, Travel Agent, Personnel and Marketing Services.',
      'desc' => 'Merupakan seseorang yang analitis, berwatak hati-hati dan ramah pada saat merasa nyaman. Ia sangat biasa dengan orang asing, karena ia dapat menilai dan menyesuaikan diri dalam hubungan mereka. Ia dapat mengembangkan hubungan baru dengan mudah ketika ia ingin melakukannya, dan pada umumnya dapat mengendalikan diri sampai pada tingkat di mana ia jarang menimbulkan rasa benci pada orang lain dengan sengaja. Ia menampilkan sikap peduli dan ramah, namun mampu memusatkan perhatian pada penyelesaian tugas yang ada. Ia cenderung perfeksionis secara alami, dan akan mengisolasi dirinya jika diperlukan untuk melaksanakan pekerjaan. Ia suka berada pada situasi yang dapat diramalkan dan tidak ada kejutan. Ia sangat berorientasi pada kualitas dan akan bekerja dengan keras untuk menyelesaikan pekerjakan dengan benar. Ia ingin orang-orang berkenan akan pekerjaan yang sudah ia selesaikan dengan baik.',
    ],
    27 => 
    [
      'key' => 28,
      'combo' => 'I-C-S',
      'archetype' => 'RESPONSIVE &amp; THOUGHTFUL',
      'traits' => 
      [
        0 => 'High Energy',
        1 => 'Good Communication Skill',
        2 => 'To The Point',
        3 => 'Sensitif',
        4 => 'Banyak Bicara',
        5 => 'Need Recognation',
        6 => 'Need Socialism',
        7 => 'Anti thd Kritik',
        8 => 'Terlalu banyak bersosialisasi',
        9 => 'Leadership kurang',
        10 => 'Kurang Fokus',
      ],
      'jobs' => 'Personnel, Welfare, Training, Attorney, Teaching, Accounting, Technical Instructor, Customer Services, Public Relations, Artist, Hotelier, Demonstrator, Engineering (Sales, Service, Project, Draughtsman, Designer], Specialist (Soft/Services], Selling, Purchasing, Supervising (Engineering, Production, Accounts], Administration Work, Secretarial, Industrial Relations Specialist.',
      'desc' => 'Merupakan individu yang berorientasi pada orang dan lancar berkomunikasi serta loyal.  Ia cenderung sensitif dan mempunyai standard yang tinggi.  Keputusannya dibuat berdasarkan fakta dan data pendukung.  Ia sepertinya tidak bisa diam.  Ia perlu untuk lebih terus terang dan jangan terlalu subyektif.  Ia butuh pengakuan sosial dan perhatian pribadi; ia dapat cepat akrab dengan orang lain.  Ia bersahabat, antusias, informal, banyak bicara dan terlalu khawatir terhadap apa yang dipikirkan orang.  Ia menguasai banyak hal.  Ia ingin diterima sebagai anggota kelompok dan ingin mengetahui secara pasti apa yang diharapkan darinya sebelum ia memulai proyek baru.',
    ],
    28 => 
    [
      'key' => 29,
      'combo' => 'S-D',
      'archetype' => 'SELF-MOTIVATED',
      'traits' => 
      [
        0 => 'Objektif &amp; Analitis',
        1 => 'Mandiri',
        2 => 'Good planner',
        3 => 'Komitmen terhadap target',
        4 => 'Menghindari konflik',
        5 => 'Ingin terlibat dalam situasi',
        6 => 'Ingin memberikan bantuan dan dukungan',
        7 => 'Termotivasi oleh target pribadi',
        8 => 'Stabil',
        9 => 'Tekun',
      ],
      'jobs' => 'Investigator, Researcher, Accountant, Engineering, Production/Engineering Supervisor, Computer Specialist, Architect, Transport/Warehouse Supervisor, Credit Controller, DP Supervisor, Computer Specialist, Research and Development, Private Investigator, Quality Controller, Engineering (Designer, Draughtsman, Project Engineer], Sales and Service Engineer, Property Manager, Attorney, Administration Manager',
      'desc' => 'Merupakan seorang yang obyektif dan analitis.  Ia ingin terlibat dalam situasi, dan juga ingin memberikan bantuan dan dukungan.  Secara internal termotivasi oleh target pribadi, Ia menyukai orang-orang, tetapi juga mempunyai kemampuan untuk berorientasi pada pekerjaannya pada saat dibutuhkan.  Karena determinasinya yang kuat, ia sering berhasil dalam berbagai hal; karakternya yang tenang, stabil dan daya tahannya memiliki kontribusi akan keberhasilannya.  Keuletannya setelah memulai pekerjaan, ia akan berusaha keras untuk mendapatkan sasarannya.  Seorang yang bebas, ia orang yang cermat dan memiliki tindak lanjut yang baik.  Ia bisa menjadi tidak ramah walaupun ia pada dasarnya ia yang berorientasi pada orang; dan pada situasi yang tidak membuatnya nyaman, ia lebih suka mendukung pemimpinnya dari pada keterlibatannya dengan situasi.',
    ],
    29 => 
    [
      'key' => 30,
      'combo' => 'S-I',
      'archetype' => 'ADVISOR',
      'traits' => 
      [
        0 => 'Hangat',
        1 => 'Simpati dan Pengertian',
        2 => 'Tenang dalam situasi sosial',
        3 => 'Pendengar yang baik',
        4 => 'Demonstratif',
        5 => 'Tidak memaksakan idenya pada orang lain',
        6 => 'Kurang tegas dalam memberi perintah',
        7 => 'Menerima kritik',
        8 => 'Toleran dan sabar',
        9 => 'Penjaga damai',
      ],
      'jobs' => 'Personnel Welfare, Training, Hotelier, Promoting, Travel Agent, Lecturing, Child Care, Charitable Organizations, Soft or Service Selling, Psychologist, Therapist, Nurse, Personal Assistant, Hospitality Manager, Social Work, Student Services, Upmarket/Speciality Sales.',
      'desc' => 'Seorang yang mengesankan orang akan kehangatan, simpati dan pengertiannya.  Ia memiliki ketenangan dalam sebagian besar situasi sosial dan jarang tidak menyenangkan orang lain.  Faktanya, banyak orang datang padanya karena ia kelihatan sebagai pendengar yang baik.  Ia cenderung sangat demonstratif dan emosinya biasanya tampak jelas bagi orang di sekitarnya.  Ia tidak akan memaksakan idenya pada orang lain; ia tidak tegas dalam mengekspresikan atau memberi perintah.  Jika ia sangat kuat merasakan sesuatu, Ia akan bicara secara terbuka dan terus terang tentang pendiriannya.  Ia cenderung menerima kritik atas pekerjaannya sebagai serangan pribadi.  Ia dapat menjadi sangat toleran dan sabar kepada mereka yang tidak produktif di pekerjaan.  Ia merupakan "penjaga damai" yang sebenarnya dan akan bekerja untuk menjaga kedamaian dalam setiap keadaan.',
    ],
    30 => 
    [
      'key' => 31,
      'combo' => 'S-D-I',
      'archetype' => 'DIRECTOR',
      'traits' => 
      [
        0 => 'Seorang yang obyektif dan analitis',
        1 => 'Ingin terlibat dalam situasi',
        2 => 'Ingin memberikan bantuan dan dukungan',
        3 => 'Termotivasi oleh target pribadi',
        4 => 'Berorientasi terhadap pekerjaannya',
        5 => 'Menyukai hubungan dengan sesama',
        6 => 'Mempunyai determinasi yang kuat',
        7 => 'Karakternya tenang',
        8 => 'Stabil dan daya tahannya tinggi',
        9 => 'Ulet dalam memulai pekerjaan',
        10 => 'Berusaha keras mencapai sasarannya',
      ],
      'jobs' => 'Engineering and Production (Supervision], Service Selling, Distribution and Warehouse Supervision/Manager, Office Management, Customer Service, System Analyst, Radio Announcer, Technical Writing, Telemarketing, TV Presenter, Project Engineer, Film Producer, Programmer, Sales/Service Engineer, Accounting, Draughtsman, Project Engineer.',
      'desc' => 'Seorang yang obyektif dan analitis.  Ia ingin terlibat dalam situasi, dan ia juga ingin memberikan bantuan dan dukungan kepada orang yang ia hormati.  Secara internal termotivasi oleh target pribadi, ia berorientasi terhadap pekerjaannya tapi juga menyukai hubungan dengan sesama.  Karena determinasinya yang kuat, ia sering berhasil dalam berbagai hal; karakternya yang tenang, stabil dan daya tahannya yang tinggi memiliki kontribusi dalam keberhasilannya.  Ulet dalam memulai pekerjaan. Ia akan berusaha keras untuk mencapai sasarannya.  Seorang yang mandiri dan cermat serta memiliki tindak lanjut yang baik.',
    ],
    31 => 
    [
      'key' => 32,
      'combo' => 'S-I-D',
      'archetype' => 'ADVISOR',
      'traits' => 
      [
        0 => 'Hangat',
        1 => 'Simpati dan Pengertian',
        2 => 'Tenang dalam situasi sosial',
        3 => 'Pendengar yang baik',
        4 => 'Demonstratif',
        5 => 'Tidak memaksakan idenya pada orang lain',
        6 => 'Kurang tegas dalam memberi perintah',
        7 => 'Menerima kritik',
        8 => 'Toleran dan sabar',
        9 => 'Penjaga damai',
      ],
      'jobs' => 'Engineering and Production (Supervision], Service Selling, Distribution and Warehouse Supervision, Office Management, Customer Service, System Analyst, Programmer, Sales/Service Engineer, Accounting, Draughtsman, Project Engineer.',
      'desc' => 'Seorang yang mengesankan orang akan kehangatan, simpati dan pengertiannya.  Ia memiliki ketenangan dalam sebagian besar situasi sosial dan jarang tidak menyenangkan orang lain.  Faktanya, banyak orang datang padanya karena ia kelihatan sebagai pendengar yang baik.  Ia cenderung sangat demonstratif dan emosinya biasanya tampak jelas bagi orang di sekitarnya.  Ia tidak akan memaksakan idenya pada orang lain; ia tidak tegas dalam mengekspresikan atau memberi perintah.  Jika ia sangat kuat merasakan sesuatu, Ia akan bicara secara terbuka dan terus terang tentang pendiriannya.  Ia cenderung menerima kritik atas pekerjaannya sebagai serangan pribadi.  Ia dapat menjadi sangat toleran dan sabar kepada mereka yang tidak produktif di pekerjaan.  Ia merupakan "penjaga damai" yang sebenarnya dan akan bekerja untuk menjaga kedamaian dalam setiap keadaan.',
    ],
    32 => 
    [
      'key' => 33,
      'combo' => 'S-I-C',
      'archetype' => 'ADVOCATE',
      'traits' => 
      [
        0 => 'Stabil',
        1 => 'Ramah',
        2 => 'Detail ketika situasi membutuhkan',
        3 => 'Cenderung individualis',
        4 => 'Teguh pendirian',
        5 => 'Menyukai hubungan dengan orang',
        6 => 'Mendukung pihak yang lemah',
        7 => 'Ingin diterima sebagai anggota tim',
        8 => 'Ingin orang lain menyukainya',
        9 => 'Sulit membuat keputusan',
        10 => 'Moderat',
      ],
      'jobs' => 'Personnel Welfare, Training, Teaching, Attorney, Accounting, Technical Instructor, Customer Service, Public Relations, Artist, Hotelier, Demonstrator, Engineer (Sales, Service, Project, Draughtsman, Designer], Specialist (Soft/Service], Selling, Purchasing, Supervising (Engineering, Production, Accounts] Administrative Work, Secretarial.',
      'desc' => 'Merupakan orang yang stabil, individu yang ramah yang berusaha keras membangun hubungan yang positif di tempat kerja dan di rumah.  Ia dapat menjadi sangat berorientasi detil ketika situasi membutuhkan; tetapi secara keseluruhan ia cenderung individualis, independen dan sedikit perhatian terhadap detil.  Sekali dia membuat keputusan, sangat sulit mengubah pendiriannya.  Ia menyukai hubungan dengan orang dan cenderung mendukung pihak yang lemah.  Ia akan mengambil posisi berlawanan dengan ketidaksepakatan dan merasa frustrasi jika sesuatu tidak sejalan dengannya.  Ia ingin diterima sebagai anggota tim, dan ia menginginkan orang lain menyukainya.  Ia cukup sulit membuat keputusan sampai parameter wewenang secara jelas ditentukan, dan ia mungkin cenderung tidak sungguh-sungguh jika dipaksa membuat keputusan ketika ia tidak ingin melakukannya.  Ia menginginkan orang lain yang membuat keputusan, khususnya jika ada orang yang sangat ia hargai dan hormati.  Ia cenderung moderat, cermat dan dapat diandalkan.',
    ],
    33 => 
    [
      'key' => 34,
      'combo' => 'S-C-D',
      'archetype' => 'INQUIRER',
      'traits' => 
      [
        0 => 'Seorang yang baik',
        1 => 'Sangat berorientasi pada detil',
        2 => 'Sangat teliti dalam penyelesaian tugas',
        3 => 'Sangat berhati-hati',
        4 => 'Penuh pertimbangan',
        5 => 'Lambat adaptasi',
        6 => 'Kaku dan keras kepala',
      ],
      'jobs' => 'Directing, Managing or Supervising (in Engineering, Accountancy, Research and Development and Computing disciplines], Accountant, Project Engineer, Draughtsman, Designer, Analyst, Chemist, Technician, Service Engineer, Manager, Security Specialist.',
      'desc' => 'Seorang yang baik secara alamiah dan sangat berorientasi detil.  Ia peduli dengan orang-orang di sekitarnya dan mempunyai kualitas yang membuatnya sangat teliti dalam penyelesaian tugas.  Ia mempertimbangkan sekelilingnya dengan hati-hati sebelum membuat keputusan untuk melihat pengaruhnya pada mereka; saat tertentu ia terlalu hati-hati.  Jika ia merasa seseorang memanfaatkan situasi, ia akan memperlambat kerjanya sehingga dapat mengamati apa yang sedang berlangsung di sekitarnya.',
    ],
    34 => 
    [
      'key' => 35,
      'combo' => 'S-C-I',
      'archetype' => 'ADVOCATE',
      'traits' => 
      [
        0 => 'Stabil',
        1 => 'Ramah',
        2 => 'Detail ketika situasi membutuhkan',
        3 => 'Cenderung individualis',
        4 => 'Teguh pendirian',
        5 => 'Menyukai hubungan dengan orang',
        6 => 'Mendukung pihak yang lemah',
        7 => 'Ingin diterima sebagai anggota tim',
        8 => 'Ingin orang lain menyukainya',
        9 => 'Sulit membuat keputusan',
        10 => 'Moderat',
      ],
      'jobs' => 'Personnel Welfare, Administrator, Advisers, Training, Teaching, Attorney, Accounting, Counseling, Technical Instructor, Customer Service, Accounting-General, Public Relations, Accounts Supervisor, Artist, Hotelier, Demonstrator, Engineer (Sales, Service, Project, Draughtsman, Designer], Specialist (Soft/Service], Selling, Purchasing, Sales Engineer, Legal, Negotiator, Student Service, Photographer, Physiotherapist, Project Engineer, Vocational Education, Supervising (Engineering, Production, Accounts] Administrative Work, Demonstrator, Secretarial, Hospitality Manager.',
      'desc' => 'Merupakan orang yang stabil, individu yang ramah yang berusaha keras membangun hubungan yang positif di tempat kerja dan di rumah.  Ia dapat menjadi sangat berorientasi detil ketika situasi membutuhkan; tetapi secara keseluruhan ia cenderung individualis, independen dan sedikit perhatian terhadap detil.  Sekali dia membuat keputusan, sangat sulit mengubah pendiriannya.  Ia menyukai hubungan dengan orang dan cenderung mendukung pihak yang lemah.  Ia akan mengambil posisi berlawanan dengan ketidaksepakatan dan merasa frustrasi jika sesuatu tidak sejalan dengannya.  Ia ingin diterima sebagai anggota tim, dan ia menginginkan orang lain menyukainya.  Ia cukup sulit membuat keputusan sampai parameter wewenang secara jelas ditentukan, dan ia mungkin cenderung tidak sungguh-sungguh jika dipaksa membuat keputusan ketika ia tidak ingin melakukannya.  Ia menginginkan orang lain yang membuat keputusan, khususnya jika ada orang yang sangat ia hargai dan hormati.  Ia cenderung moderat, cermat dan dapat diandalkan.',
    ],
    35 => 
    [
      'key' => 36,
      'combo' => 'C-I',
      'archetype' => 'ASSESSOR',
      'traits' => 
      [
        0 => 'Analitis',
        1 => 'Berwatak hati-hati',
        2 => 'Ramah pada saat merasa nyaman',
        3 => 'Sangat biasa dengan orang asing',
        4 => 'Mudah mengembangkan hubungan baru',
        5 => 'Dapat mengendalikan diri',
        6 => 'Peduli dan ramah',
        7 => 'Memusatkan perhatian pada penyelesaian tugas',
        8 => 'Perfeksionis secara alami',
        9 => 'Mengisolasi dirinya jika diperlukan',
        10 => 'Mudah diramalkan',
      ],
      'jobs' => 'Sales (Technical/Specialist], Public Relations, Lecturer, Academic, Personnel Administration, Purchasing, Travel Agent, Training, Teaching, Real Estate Agent, Hospitality Administration, Sales-Technical, Hotelier, Project Engineer, Service Engineer.',
      'desc' => 'Merupakan seseorang yang analitis, berwatak hati-hati dan ramah pada saat merasa nyaman. Ia sangat biasa dengan orang asing, karena ia dapat menilai dan menyesuaikan diri dalam hubungan mereka. Ia dapat mengembangkan hubungan baru dengan mudah ketika ia ingin melakukannya, dan pada umumnya dapat mengendalikan diri sampai pada tingkat di mana ia jarang menimbulkan rasa benci pada orang lain dengan sengaja. Ia menampilkan sikap peduli dan ramah, namun mampu memusatkan perhatian pada penyelesaian tugas yang ada. Ia cenderung perfeksionis secara alami, dan akan mengisolasi dirinya jika diperlukan untuk melaksanakan pekerjaan. Ia suka berada pada situasi yang dapat diramalkan dan tidak ada kejutan. Ia sangat berorientasi pada kualitas dan akan bekerja dengan keras untuk menyelesaikan pekerjakan dengan benar. Ia ingin orang-orang berkenan akan pekerjaan yang sudah ia selesaikan dengan baik.',
    ],
    36 => 
    [
      'key' => 37,
      'combo' => 'C-D-I',
      'archetype' => 'CHALLENGER',
      'traits' => 
      [
        0 => 'Sangat berorientasi pada tugas',
        1 => 'Sensitif terhadap permasalahan',
        2 => 'Lebih mempedulikan tugas daripada orang',
        3 => 'Kukuh/keras',
        4 => 'Dingin',
        5 => 'Tidak berperasaan',
        6 => 'Menjaga jarak',
        7 => 'Membuat keputusan berdasarkan fakta',
        8 => 'Pendiam',
        9 => 'Tidak mudah percaya',
      ],
      'jobs' => 'Directing, Managing or Supervising (Engineering, Research, Finance, Planning], Designer, Work Study, Sales (Technical/ Specialist], Logistic Support, Systems Analyst, Lecturer, Company Secretary, Negotiator and Purchasing.',
      'desc' => 'Seorang yang sangat berorientasi pada tugas dan sensitif pada permasalahan. Ia lebih mempedulikan tugas yang ada dibanding orang-orang di sekitarnya, termasuk perasaan mereka. Ia sangat kukuh/keras dan mempunyai pendekatan yang efektif dalam pemecahan masalah. Oleh karena sifat alamiah dan keinginannya akan hasil yang terukur, ia akan tampak dingin, tidak berperasaan dan menjaga jarak. Ia membuat keputusan berdasar pada fakta, bukan emosi. ia cenderung pendiam dan tidak mudah percaya.',
    ],
    37 => 
    [
      'key' => 38,
      'combo' => 'C-D-S',
      'archetype' => 'CONTEMPLATOR',
      'traits' => 
      [
        0 => 'Berorientasi pada hal-hal detil',
        1 => 'Mempunyai standar tinggi untuk dirinya',
        2 => 'Logis dan analitis',
        3 => 'Ingin berbuat yang terbaik',
        4 => 'Selalu berpikir ada ruang untuk kemajuan',
        5 => 'Kompetitif',
        6 => 'Ingin menghasilkan mutu yang terbaik',
        7 => 'Mampu mencapai sasarannya',
        8 => 'Sangat memusatkan perhatian pada tugas',
        9 => 'Mantap dan dapat diandalkan',
      ],
      'jobs' => 'Engineering, Research, Production and Finance (Director, Manager atau Supervisor], Work Study, Accountant, Administrator, Quality Controller, Safety Officer, Market Analyst, Planner and Personnel (Director, Manager, Administrator], MIS Manager, Security Manager, Loss Control.',
      'desc' => 'Berorientasi pada hal detil dan mempunyai standard tinggi untuk dirinya. Ia logis dan analitis. Ia ingin berbuat yang terbaik, dan ia selalu berpikir ada ruang untuk peningkatan/kemajuan. Ia cenderung kompetitif dan ingin menghasilkan pekerjaan dengan mutu yang terbaik. Ia sebenarnya sensitif terhadap orang-orang, tetapi karena sifat logisnya, orientasinya terhadap tugas dapat menutupinya dengan mudah. Ia suka dihargai untuk pekerjaannya yang berkualitas. Ia mampu mengerjakan tugas-tugas; dan mencapai sasarannya. Ia sangat memusatkan perhatian pada tugas yang ada, mantap dan dapat diandalkan.',
    ],
    38 => 
    [
      'key' => 39,
      'combo' => 'C-I-D',
      'archetype' => 'ASSESSOR',
      'traits' => 
      [
        0 => 'Analitis',
        1 => 'Berwatak hati-hati',
        2 => 'Ramah pada saat merasa nyaman',
        3 => 'Sangat biasa dengan orang asing',
        4 => 'Mudah mengembangkan hubungan baru',
        5 => 'Dapat mengendalikan diri',
        6 => 'Peduli dan ramah',
        7 => 'Memusatkan perhatian pada penyelesaian tugas',
        8 => 'Perfeksionis secara alami',
        9 => 'Mengisolasi dirinya jika diperlukan',
        10 => 'Mudah diramalkan',
      ],
      'jobs' => 'Directing, Managing or Supervising (Engineering, Research, Finance, Planning], Designer, Work Study, Sales (Technical/Specialist], Lecturer, Company Secretary, Negotiator and Purchasing.',
      'desc' => 'Merupakan seseorang yang analitis, berwatak hati-hati dan ramah pada saat merasa nyaman. Ia sangat biasa dengan orang asing, karena ia dapat menilai dan menyesuaikan diri dalam hubungan mereka. Ia dapat mengembangkan hubungan baru dengan mudah ketika ia ingin melakukannya, dan pada umumnya dapat mengendalikan diri sampai pada tingkat di mana ia jarang menimbulkan rasa benci pada orang lain dengan sengaja. Ia menampilkan sikap peduli dan ramah, namun mampu memusatkan perhatian pada penyelesaian tugas yang ada. Ia cenderung perfeksionis secara alami, dan akan mengisolasi dirinya jika diperlukan untuk melaksanakan pekerjaan. Ia suka berada pada situasi yang dapat diramalkan dan tidak ada kejutan. Ia sangat berorientasi pada kualitas dan akan bekerja dengan keras untuk menyelesaikan pekerjakan dengan benar. Ia ingin orang-orang berkenan akan pekerjaan yang sudah ia selesaikan dengan baik.',
    ],
    39 => 
    [
      'key' => 40,
      'combo' => 'C-S-D',
      'archetype' => 'PRECISIONIST',
      'traits' => 
      [
        0 => 'Sistematis dan Prosedural',
        1 => 'Teratur &amp; memiliki perencanaan yang baik',
        2 => 'Teliti',
        3 => 'Fokus pada detil',
        4 => 'Bijaksana',
        5 => 'Diplomatis',
        6 => 'Jarang menentang rekan kerjanya',
        7 => 'Ia sangat berhati-hati',
        8 => 'Mengharapkan akurasi dan standard tinggi',
        9 => 'Menginginkan adanya petunjuk standard',
        10 => 'Tidak menginginkan perubahan mendadak',
      ],
      'jobs' => 'Engineering, Research Director, Production and Finance (Director, Manager, Supervisor], Work Study, Accountant, Administrator, Quality Controller, Financial Services Manager, Safety Officer, Market Analyst, Planner and Personnel (Director, Manager, Administrator], MIS Manager, Electrician, Security Manager, Financial Researcher, Planner, Printer, Production Controller, Production Manager, Personnel Management, Loss Control.',
      'desc' => 'Berpikir sistematis dan cenderung mengikuti prosedur dalam kehidupan pribadi dan pekerjaannya.  Teratur dan memiliki perencanaan yang baik, ia teliti dan fokus pada detil.  Ia bertindak dengan penuh kebijaksanaan, diplomatis dan jarang menentang rekan kerjanya dengan sengaja.  Ia sangat berhati-hati, ia sungguh-sungguh mengharapkan akurasi dan standard tinggi dalam pekerjaannya.  Ia cenderung terjebak dalam hal detil, khususnya jika harus memutuskan.  ia menginginkan adanya petunjuk standard pelaksanaan kerja dan tanpa perubahan mendadak.',
    ],
  ],
];
