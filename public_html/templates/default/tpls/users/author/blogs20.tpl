<?php

$FORMS = [];

$FORMS['user_block'] = <<<END
<span class="user">
	<a href="/users/profile/%user_id%/" style="text-decoration:none;" title="Автор">
		<img src="/images/cms/blogs20/user.png" alt="Зарегистрированный пользователь" title="Зарегистрированный пользователь" align="middle" style="border:0;" />			
		<span style="font-weight:bold;padding-left:4px;">%login%</span>
	</a>
</span>
END;

$FORMS['guest_block'] = <<<END
<span class="guest">
	<img src="/images/cms/blogs20/guest.png" alt="Незарегистрированный пользователь" title="Незарегистрированный пользователь" align="middle" />	
	<span style="color:#555;font-weight:bold;padding-left:4px;">%nickname%</span>
</span>
END;

$FORMS['sv_block'] = <<<END
<span class="user">
	<a href="/users/profile/%user_id%/" style="text-decoration:none;"  title="Автор">
		<img src="/images/cms/blogs20/user.png" alt="Зарегистрированный пользователь" title="Зарегистрированный пользователь" align="middle" style="border:0;" />			
		<span style="font-weight:bold;padding-left:4px;">%login%</span>
	</a>
</span>
END;


?>
