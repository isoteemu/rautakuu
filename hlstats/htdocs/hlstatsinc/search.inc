<?php
	/*
	 * HLstats - Real-time player and clan rankings and statistics for Half-Life
	 * http://sourceforge.net/projects/hlstats/
	 *
	 * Copyright (C) 2001  Simon Garner
	 *
	 * This program is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License
	 * as published by the Free Software Foundation; either version 2
	 * of the License, or (at your option) any later version.
	 *
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with this program; if not, write to the Free Software
	 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
	 */


	// Search

	require_once(INCLUDE_PATH . "/search-class.inc");

	pageHeader(
		array(_("Search")),
		array(_("Search")=>"")
	);

	$sr_query = strval($HTTP_GET_VARS["q"]);
	$sr_type  = strval($HTTP_GET_VARS["st"])
		or "player";
	$sr_game  = strval($HTTP_GET_VARS["game"]);

	$search = new Search($sr_query, $sr_type, $sr_game);

	$search->drawForm(array("mode"=>"search"));
	if ($sr_query || $sr_query == "0") $search->drawResults();
?>

