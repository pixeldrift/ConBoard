<?php
$loc = wp_insert_post(['post_type'=>'location','post_title'=>'Main Hall','post_status'=>'publish']);
$lobby = wp_insert_post(['post_type'=>'location','post_title'=>'Lobby','post_status'=>'publish']);
$now = current_time('timestamp');
foreach ([['Opening Keynote',0,60],['Panel: Signage',90,150]] as [$t,$a,$b]) {
  $id = wp_insert_post(['post_type'=>'session','post_title'=>$t,'post_status'=>'publish']);
  update_post_meta($id,'start_time',date('Y-m-d H:i:s',$now+$a*60));
  update_post_meta($id,'end_time',date('Y-m-d H:i:s',$now+$b*60));
  update_post_meta($id,'location',$loc);
}
$boards = get_posts(['post_type'=>'display_board','numberposts'=>-1,'post_status'=>'any']);
echo "boards: ".count($boards)."\n";
$b2 = wp_insert_post(['post_type'=>'display_board','post_title'=>'Sponsor Loop','post_status'=>'publish']);
update_post_meta($b2,'_conboard_board',['rotate'=>'0','orientation'=>'landscape']);
update_post_meta($b2,'_conboard_slides',[['type'=>'custom','enabled'=>true,'duration'=>5,'settings'=>['heading'=>'Thanks to our sponsors','text'=>'Hello']]]);
$s1 = wp_insert_post(['post_type'=>'venue_screen','post_title'=>'Lobby TV','post_status'=>'publish']);
update_post_meta($s1,'_conboard_screen',conboard_sanitize_screen_settings(['location'=>$lobby,'resolution'=>'1920x1080','unit'=>'roku','board'=>$boards[0]->ID ?? 0]));
$s2 = wp_insert_post(['post_type'=>'venue_screen','post_title'=>'Hall Portrait','post_status'=>'publish']);
update_post_meta($s2,'_conboard_screen',conboard_sanitize_screen_settings(['location'=>$loc,'resolution'=>'3840x2160','unit'=>'tv_browser','rotate'=>'90']));
flush_rewrite_rules();
echo "screens: $s1 $s2 board2: $b2\n";
