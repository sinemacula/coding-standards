<?php

namespace App;

enum Suit
{
    /** The hearts suit. */
    case HEARTS;

    case DIAMONDS;

    /** The clubs suit. */
    case CLUBS;
}

enum Status
{
    case ACTIVE;
    case INACTIVE;
}
