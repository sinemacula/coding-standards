<?php

enum Suit: string
{
    case HEARTS = 'hearts';
    case DIAMONDS = 'diamonds';
    case Clubs = 'clubs';
    case spades = 'spades';
}

enum Status
{
    case ACTIVE;
    case In_Progress;
}
