<?php

// Get all authors from the custom table
function ms_get_authors()
{
    global $wpdb;

    $prefix = "ms_";
    $table = $prefix . "authors";
    $sql = "SELECT id, name FROM $table";

    return $wpdb->get_results($sql);
}

// Get books by a specific author
function ms_get_books_by_author($author_id)
{
    global $wpdb;

    $prefix = "ms_";
    $table = $prefix . "books";
    $sql = "SELECT id, name FROM $table WHERE author_id = $author_id";

    return $wpdb->get_results($sql);
}

// Search for keyword in filtered books
function ms_search_on_books($author_id, $keyword, $book_id = 0)
{
    global $wpdb;

    $prefix = "ms_";
    $quoteTable = $prefix . "book_quotes";
    $codeTable = $prefix . "book_codes";
    $bookTable = $prefix . "books";
    $authorTable = $prefix . "authors";
    $sql = "";

    if ($author_id == 5) {
        $quoteTable = $prefix . "new_testament_quotes";
    }

    if ($author_id === 1) {
        $quoteTable = $prefix . "all_book_quotes";

        $sql .= "WITH $quoteTable AS (
        SELECT * FROM ms_book_quotes
        UNION
        SELECT * FROM ms_new_testament_quotes
        )
        ";
    }

    $sql .= "SELECT atbl.name as author, btbl.name as book, ctbl.abbreviation as abbreviation, qtbl.chapter as chapter, qtbl.numeral as numeral, qtbl.quote as quote
    FROM " . $quoteTable . " qtbl, " . $codeTable . " ctbl, " . $bookTable . " btbl, " . $authorTable . " atbl
    WHERE qtbl.book_code_id = ctbl.id
    AND btbl.id = ctbl.book_id
    AND atbl.id = btbl.author_id ";

    if ($author_id !== 1) {
        $sql .= "AND atbl.id = " . $author_id . " ";
    }

    if (! in_array($book_id, [55, 56, 57, 58], true)) {
        $sql .= "AND btbl.id = " . $book_id . " ";
    }

    $sql .= 'AND qtbl.quote LIKE "%' . $keyword . '%";';

    return $wpdb->get_results($sql);
}
