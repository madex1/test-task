<?= '<?xml version="1.0" encoding="utf-8"?>';?>
<result xmlns:xlink="http://www.w3.org/TR/xlink">
	<data>
		<error code="<?= $v42552b1f133f9f8eb406d4f306ea9fd1->code;?>" type="<?= $v42552b1f133f9f8eb406d4f306ea9fd1->type;?>"><?= $v42552b1f133f9f8eb406d4f306ea9fd1->message;?></error>
		<?php
  if (DEBUG_SHOW_BACKTRACE):   ?>
			<backtrace><?php
   $v29d5f56fb4447d05677b6226b698efe5 = explode("\n", $v42552b1f133f9f8eb406d4f306ea9fd1->traceAsString);foreach ($v29d5f56fb4447d05677b6226b698efe5 as $v04a75036e9d520bb983c5ed03b8d0182):    ?>
				<trace><?= $v04a75036e9d520bb983c5ed03b8d0182 ?></trace><?php
   endforeach;?></backtrace><?php
  endif;?>
	</data>
</result>
