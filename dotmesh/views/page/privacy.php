<!--{ title: Privacy }-->
<!--{ meta_title: Privacy }-->
<!--{ meta_description: Privacy }-->
<!--{ meta_keywords: Privacy }-->

<div class="site-main form-col1-layout clearfix">
	<h2 class="page-title">Privacy</h2>
	<div class="content">
        <p>Privacy page coming soon.</p>

		<p>While we take your privacy seriously we recommend to get your own DotMesh node so you can be certain.</p>

        <p>Some security measures we implement on this site for technologically inclined:</p>
        <ul>
<li>User passwords are hashed using bcrypt with difficulty 10</li>
<li>Remote users validated using SHA512 double hash:<br/>
    SHA512( [agent ip] * SHA512( [local node secret key] * [remote node secret key] * [user secret key] ))</li>
<li>Remote user signatures are re-validated against claiming node when needed</li>
<li>Server to server communication is DNS validated</li>
<li>Use SSL when available</li>
        </ul>
	</div>
</div>