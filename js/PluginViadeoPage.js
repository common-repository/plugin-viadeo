/*  Copyright 2009  DimGoTo  (email : wordpress@dimgoto.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/**
 * Plugin Viadéo.
 *
 *	Page
 *
 * @package Plugins
 * @subpackage Viadeo
 * @version 1.0.0
 * @author Dimitri GOY
 * @copyright 2009 - DimGoTo
 */
jQuery(document).ready(function($) {
	
	var win_viadeo = null;
		
	$('#' + viadeo_idprefix + '-profiles > *').click(function(event) {
		
		var id = event.target.id;
		var elname = viadeo_idprefix + '-profile-';
		if (id.substring(0, elname.length) == elname) {
			var idprofile = id.substring(elname.length);
			var url = $('#' + viadeo_idprefix + '-url-' + idprofile).val();
			if (url != null) {
				if (win_viadeo == null) {
					win_viadeo = window.open(url, "Window Viadeo");
				} else {
					win_viadeo.location = url;
				}
			} else {
				$('#notice').text();
				$('#notice').show();
				$('#notice').fadeOut(10000);
			}
		}
		return false;
	});

});