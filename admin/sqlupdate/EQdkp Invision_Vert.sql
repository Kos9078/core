DELETE FROM eqdkp_styles where style_id=25;
DELETE FROM eqdkp_style_config where style_id=25;
INSERT INTO eqdkp_styles (style_id, style_name, template_path, body_background, body_link, body_link_style, body_hlink, body_hlink_style, header_link, header_link_style, header_hlink, header_hlink_style, tr_color1, tr_color2, th_color1, fontface1, fontface2, fontface3, fontsize1, fontsize2, fontsize3, fontcolor1, fontcolor2, fontcolor3, fontcolor_neg, fontcolor_pos, table_border_width, table_border_color, table_border_style, input_color, input_border_width, input_border_color, input_border_style) VALUES (25, 'EQdkp Invision_Vert', 'defaultV', 'FFFFFF', '000000', 'underline', '465584', 'underline', '3A4F6C', 'none', '3A4F6C', 'underline', 'E4EAF2', 'DFE6EF', 'BCD0ED', 'Verdana, Tahoma, Arial', 'Verdana, Tahoma, Arial', 'Verdana, Tahoma, Arial', 10, 11, 12, '000000', '000000', '000000', 'F80000', '008800', 1, '345487', 'solid', 'FFFFFF', 1, '000000', 'solid');
INSERT INTO eqdkp_style_config (style_id, attendees_columns, logo_path) VALUES (25, '6', 'bc_header3.gif');