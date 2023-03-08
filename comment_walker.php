<?php
require_once ABSPATH . '/comments_like_dislike/core/comments_like_dislike_core.php';

/** COMMENTS WALKER */
class My_Comment_Walker extends Walker_Comment {

	// initialize the class-wide variables
	var $tree_type = 'comment';
	var $db_fields = array( 'parent' => 'comment_parent', 'id' => 'comment_ID' );
 
	/** CONSTRUCTOR
	 * You'll have to use this if you plan to get to the top of the comments list, as
	 * start_lvl() only goes as high as 1 deep nested comments */
	function __construct() { 
		?><ul id="comment-list"><?php
     }


	/** START_LVL 
	 * Starts the list before the CHILD elements are added. */
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1; 
		?><ul class="children"><?php
	}
 
	/** END_LVL 
	 * Ends the children list of after the elements are added. */
	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1; 
		?></ul><?php
	}

	/** START_EL */
	function start_el( &$output, $comment, $depth, $args, $id = 0 ) {
		$depth++;
		$GLOBALS['comment_depth'] = $depth;
		$GLOBALS['comment'] = $comment; 
		$parent_class = ( empty( $args['has_children'] ) ? '' : 'parent' ); 

		?><li <?php comment_class( $parent_class ); ?> id="comment-<?php comment_ID() ?>">
			<div class="comment" id="comment-<?php comment_ID() ?>"
				><div class="commenter-pic"><?php
					echo ( $args['avatar_size'] != 0 ? get_avatar( $comment, $args['avatar_size'] ) : '' ); 
				?></div><div class="arrow-right"></div
				><div class="comment-data"
					><div class="comment-data-nameAndDate"><?php
						$reply_args = array(
												//'add_below' => $add_below, 
												'depth' => $depth,
												'max_depth' => $args['max_depth'],
												'reply_text' => 'Reply',
											);
						
						?><a href="<?php echo htmlspecialchars( get_comment_link( get_comment_ID() ) ); ?>">Comment</a> <?php
						comment_author_link(); 
						?> <span>at <?php echo get_comment_date(); ?> <?php echo get_comment_time(); ?></span>
					</div
					><div class="comment-data-mainData"><?php
						if(!$comment->comment_approved) : 
							?><div class="comment-awaiting-moderation-text"><?php comment_text(); ?></div>
							<div class="comment-awaiting-moderation">Your comment is awating moderation.</div><?php
						else: 
							comment_text();
						endif;
					?></div>
					<div class="comment-operations">
						<?php 
							$edit_comment_link = get_edit_comment_link();
							if(!empty($edit_comment_link)) {
								$edit_comment_link = '<span class="edit-comment-link"><a href="'.$edit_comment_link.'">Edit</a></span>';
							}
							echo $edit_comment_link;
						?>
						<?php 
							$client_opinion_on_the_comment = comments_like_dislike::get_opinion_row(get_comment_ID(), $_SERVER['REMOTE_ADDR']);
							$client_already_liked_the_comment = $client_already_disliked_the_comment = false;
							if(!empty($client_opinion_on_the_comment)) {
								$client_already_liked_the_comment = $client_opinion_on_the_comment['opinion'] == comments_like_dislike::OPINION_LIKE;
								$client_already_disliked_the_comment = $client_opinion_on_the_comment['opinion'] == comments_like_dislike::OPINION_DISLIKE;
							}
						?>
						<span
							id="like_<?php comment_ID(); ?>"
							class="like<?php if($client_already_liked_the_comment) echo ' already-liked'; else if($client_already_disliked_the_comment) echo ' default-cursor'; ?>"
							<?php if(!$client_already_liked_the_comment && !$client_already_disliked_the_comment): ?>onclick="like_comment(<?php comment_ID(); ?>);"<?php endif; ?>
							title="<?php if($client_already_liked_the_comment) echo 'You liked this comment at '.$client_opinion_on_the_comment['insert_date']; else echo $client_already_disliked_the_comment ? '' : 'I like this comment' ;?>"
						>
							<?php echo comments_like_dislike::count_like_opinions(get_comment_ID()); ?>
						</span>
						<span id="comment_like_dislike_ajax_loading_<?php comment_ID(); ?>" class="loading"></span>
						<span
							id="dislike_<?php comment_ID(); ?>"
							class="dislike<?php if($client_already_disliked_the_comment) echo ' already-disliked'; else if($client_already_liked_the_comment) echo ' default-cursor'; ?>" 
							<?php if(!$client_already_liked_the_comment && !$client_already_disliked_the_comment): ?>onclick="dislike_comment(<?php comment_ID(); ?>);"<?php endif; ?>
							title="<?php if($client_already_disliked_the_comment) echo 'You disliked this comment at '.$client_opinion_on_the_comment['insert_date']; else echo $client_already_liked_the_comment ? '' : "I don't like this comment";?>"
						>
							<?php echo comments_like_dislike::count_dislike_opinions(get_comment_ID()); ?>
						</span>
						<span class="comment-reply"><?php comment_reply_link( array_merge( $args, $reply_args ) ); ?></span>
					</div>
				</div>
			</div><?php
			
			if($args['has_children']) :
				?><div class="comments-replies-icon" title="Replies to '<?php comment_author(); ?>'"></div><?php
			else:
				?><div class="comment-has-no-replies"></div><?php
			endif;
	} // end of method
 
	function end_el(&$output, $comment, $depth = 0, $args = array() ) { 
		?></li><?php
	}

	/** DESTRUCTOR
	 * I'm just using this since we needed to use the constructor to reach the top 
	 * of the comments list, just seems to balance out nicely:) */
	function __destruct() { 
		?></ul><?php
	}
}