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
 *	Configuration.
 *
 * @package Plugins
 * @subpackage Viadeo
 * @version 1.0.0
 * @author Dimitri GOY
 * @copyright 2009 - DimGoTo
 */
jQuery(document).ready(function($) {
	
	$('#notice').hide();
	$('#message').hide();
	$('#' + viadeo_idprefix + '-overlay').hide();
	$('#' + viadeo_idprefix + '-update').hide();
	
	update_list();
	
	$(':checkbox').click(function(e) {
		
		var id = e.target.id;
		var elname = viadeo_idprefix + '-checkbox-';
		
		if (id == viadeo_idprefix + '-all1' 
		|| id == viadeo_idprefix + '-all2') {
			clear_form();	
		} else if (id.substring(0, elname.length) == elname) {
			var id = null;
			var i = 0;
			if ($('input:checkbox:checked').length == 1) {
				$('input:checkbox:checked').each(function() {
					id = $(this).val();
				});
				$('#' + viadeo_idprefix + '-name').val($('#' + viadeo_idprefix + '-name-' + id).text());
				$('#' + viadeo_idprefix + '-url').val($('#' + viadeo_idprefix + '-url-' + id).text());
				$('#' + viadeo_idprefix + '-comment').val($('#' + viadeo_idprefix + '-comment-' + id).text());
				$('#' + viadeo_idprefix + '-add').hide();
				$('#' + viadeo_idprefix + '-update').show();
			} else {
				clear_form();		
			}
		}
		
	});
	$('#' + viadeo_idprefix + '-doaction-list').click(function() {
		
		$('#' + viadeo_idprefix + '-action-list option:selected').each(function () {
            if ($(this).val() == 'delete') {
            	var idlist = [];
            	var i = 0;
            	var elname = viadeo_idprefix + '-checkbox-';
            	$('input:checkbox:checked').each(function() {
            		if ($(this).attr('id').substring(0, elname.length) == elname) {
            			idlist[i] = $(this).val();
            			i++;
            		}
            	});
            	if (idlist.length > 0) {
            		var ids = idlist.join();
            		var params = ({
            			action: "viadeo_delete", 
            			ids: ids,
            			cookie: encodeURIComponent(document.cookie)
            		});
            		execute_ajax(params, 'delete');
            		update_list();
            	} else {
            		show_notice(viadeo_select_any);
            		clear_checkbox();
            	}
            }
        });
		
		return false;
	});
	$('#' + viadeo_idprefix + '-add').click(function() {
		if ($('#' + viadeo_idprefix + '-name').val() == '' 
		|| $('#' + viadeo_idprefix + '-url').val() == '') {
			show_notice(viadeo_required);
			return;
		} else {
			var params = ({
				action: "viadeo_add", 
				name: $('#' + viadeo_idprefix + '-name').val(),
				url: $('#' + viadeo_idprefix + '-url').val(),
				comment: $('#' + viadeo_idprefix + '-comment').val(),
				cookie: encodeURIComponent(document.cookie)
			});
			execute_ajax(params, 'add');
			clear_form();
			update_list();
		}
		
		return false;
	});
	$('#' + viadeo_idprefix + '-update').click(function() {
		if ($('input:checkbox:checked').length != 1) {
			show_notice(viadeo_update_select);
			return;
		} else if	($('#' + viadeo_idprefix + '-name').val() == '' 
		|| $('#' + viadeo_idprefix + '-url').val() == '') {
			show_notice(viadeo_required);
			return;
		} else {
			var params = ({
				action: "viadeo_update",
				id: $('input:checkbox:checked').val(),
				name: $('#' + viadeo_idprefix + '-name').val(),
				url: $('#' + viadeo_idprefix + '-url').val(),
				comment: $('#' + viadeo_idprefix + '-comment').val(),
				cookie: encodeURIComponent(document.cookie)
			});
			execute_ajax(params, 'update');
			update_list();
		}
		
		return false;
	});
	$('#message').ajaxSuccess(function(event, request, options) {
		$(this).show();
		$(this).fadeOut(10000);
	});
	$('#notice').ajaxError(function(evt, request, options) {
		$(this).show();
		$(this).fadeOut(10000);
	});
	$('#' + viadeo_idprefix + '-overlay').ajaxStart(function(){
		$(this).show();
	});
	$('#' + viadeo_idprefix + '-overlay').ajaxStop(function(){
	   $(this).hide();
	});
	function update_list() {
		var params = ({
		 	action: "viadeo_list",
		 	cookie: encodeURIComponent(document.cookie)
		});
		var list = execute_ajax(params, 'list');
		clear_list(); 
		if (list.length > 0) {
			for (var i = 0; i < list.length; i++) {
				$('#' + viadeo_idprefix + '-profiles').append('<tr id="' + viadeo_idprefix + '-profile-' + list[i].id + '" class="alternate"></tr>');
				$('#' + viadeo_idprefix + '-profile-' + list[i].id).append('<th class="check-column" scope="row" id="th-' + list[i].id + '"></th>');
				$('#th-' + list[i].id).append('<input id="' + viadeo_idprefix + '-checkbox-' + list[i].id + '" class="administrator" type="checkbox" value="' + list[i].id + '" name="' + viadeo_idprefix + '-checkbox[]">');
				$('#' + viadeo_idprefix + '-profile-' + list[i].id).append('<td class="name column-name"><strong><span id="' + viadeo_idprefix + '-name-' + list[i].id + '">' + list[i].name + '</span></strong></td>');
				$('#' + viadeo_idprefix + '-profile-' + list[i].id).append('<td class="url column-url"><span id="' + viadeo_idprefix + '-url-' + list[i].id + '">' + list[i].url + '</span></td>');
				$('#' + viadeo_idprefix + '-profile-' + list[i].id).append('<td class="comment column-comment"><span id="' + viadeo_idprefix + '-comment-' + list[i].id + '">' + list[i].comment + '</span></td>');	
			}
		} else {
			$('#' + viadeo_idprefix + '-profiles').append('<tr id="' + viadeo_idprefix + '-noprofile" class="alternate"></tr>');
			$('#' + viadeo_idprefix + '-noprofile').append('<td colspan="4">' + viadeo_empty + '</td>');
		}
	}
	function show_message(msg) {
		$('#message > p').text(msg);
		$('#message').show();
		$('#message').fadeOut(10000);
	}
	function show_notice(msg) {
		$('#notice > p').text(msg);
		$('#notice').show();
		$('#notice').fadeOut(10000);
	}
	function clear_form() {
		$('#' + viadeo_idprefix + '-name').val('');
		$('#' + viadeo_idprefix + '-url').val('');
		$('#' + viadeo_idprefix + '-comment').val('');
		$('#' + viadeo_idprefix + '-update').hide();
		$('#' + viadeo_idprefix + '-add').show();
	}
	function clear_checkbox() {
		$(':checkbox').attr('checked', false);
	}
	function clear_list() {
		$('#' + viadeo_idprefix + '-profiles > *').remove();
	}

	function execute_ajax(params, action) {
		var json = null;
		$.ajax({
			async: false,
			type: "POST",
			url: ajaxurl, 
			data: params,
			success: function(data) {
				if (action == 'list') {
					json = eval('(' + data.substring(0, data.length -1) + ')');
				} else {
					$('#message').text(data.substring(0, data.length -1));
				}
			},
			error: function(data) {
				$('#notice').text(data.substring(0, data.length -1));
			}
		});

		return json;
	}
});