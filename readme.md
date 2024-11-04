<p>This CMS Made Simple module, adds the ability to Import & Export content. Including importing Wordpress Posts or Pages to Content Manager or a LISE instance.</p>


<p>THE REST NEEDS UPDATING...</p>

In Wordpress go to Tools > Export, and choose either Posts or Pages. (Tip: maybe rename each file so you know which is posts and which is pages).

Note: this module should be installed when required and then uninstalled 
Permissions: only admin users can access and use this module


<p>This module allows copying blog posts from a WordPress&trade; installation on the same server (or with proper filesystem and database access) into CMSMS News module to aide in the transition from WordPress to CMS Made Simple.</p>
<p>This module is a fork of the CG_WP2CGBlog module created by Calguy.</p>
<h3>Features:</h3>
<ul>
  <li>Copy published blog posts.</li>
  <li>Creates a News friendly URL for each post.</li>
  <li>Translate and copy images</li>
  <li>Copies WordPress categories into News (currently just select one default category)</li>
  <li>WP Post thumbnails can be imported to a specified News custom field</li>
</ul><br>


<h3>Updates Required</h3>
<p>This module is functioning, but a few shortcuts were included to get the working, plus some more works is required before it could be released.</p>
<ul>
  <li>Categories are not being imported. Instead select a default category to set for all imported articles.</li>
  <li>Select a Backend User to set as the author for all imported articles.</li>
  <li>Module files & copyright text all needs tidying up</li>
  <li>Currently only imports WP Blog Posts - ideally would also include the CGContentUtils functionality so pages can be exported & imported.</li>
  <li>make sure all FEU references are removed, move from CGExtensions to CMSMSExt & set it as a dependency</li>
</ul><br>


<h3>How do I use it:</h3>
  <div class="warning">
    <p><strong>Note:</strong> You will probably need to perform multiple tests on various configuration items (particularly when importing meta tags, or images).  You should perform testing either in a development environmnet or with a blank install of CGBlog so that the data can be easily erased between tests.</p>
    <p><strong>Note:</strong> To use this module you need access to the wp-config.php file of your WordPress installation, and to know the complete filesystem path (absolute path) to the wordpress installation.</p>
    <p><strong>Note:</strong> This is not a complete migration from WordPress to CMSMS.  And there is no direct one-to-one conversion for URLS.  This process migrates the data. You may need to be inventive with URL rewrite rules to retain SEO ranking.</p>
  </div>
  <ol>
    <li>Navigate to the ImporterExporter admin panel inside the CMSMS admin console.</li>
    <li>Complete the form, specifying the details of the wordpress installation and what to import:
    </li>
    <li>Press Submit</li>
    <li>Review the blog articles in the frontend of the website and in the CGBlog admin panel.</li>
    <li>Do a search re-index</li>
    <li>If everything is proper you can uninstall this module.</li>
  </ol>

<h3>Future Development:</h3>
  <p>The following features are awaiting sponsorship:</p>
  <ul>
    <li>Import Comments from WordPress into CGFeedback</li>
    <li>Convert wordpress slugs (that form the permalink) into CGBlog URLs</li>
    <li>Custom field importation</li>
    <li>Groups and FEU Mapping from WordPress into FEU</li>
  </ul>

<h3>Support</h3>
<p>This module does not include commercial support. However, there are a number of resources available to help you with it:</p>
<ul>
<li>For the latest version of this module, FAQs, or to file a Bug Report or buy commercial support, please visit calguy\'s
module homepage at <a href="http://calguy1000.com">calguy1000.com</a>.</li>
<li>Additional discussion of this module may also be found in the <a href="http://forum.cmsmadesimple.org">CMS Made Simple Forums</a>.</li>
<li>The author, calguy1000, can often be found in the <a href="irc://irc.freenode.net/#cms">CMS IRC Channel</a>.</li>
<li>Lastly, you may have some success emailing the author directly.</li>
</ul>
<h3>Copyright and License</h3>
<p>Copyright &copy; 2014, Robert Campbel <a href="mailto:calguy1000@cmsmadesimple.org">&lt;calguy1000@cmsmadesimple.org&gt;</a>. All Rights Are Reserved.</p>
<p>This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.</p>
<p>However, as a special exception to the GPL, this software is distributed
as an addon module to CMS Made Simple.  You may not use this software
in any Non GPL version of CMS Made simple, or in any version of CMS
Made simple that does not indicate clearly and obviously in its admin
section that the site was built with CMS Made simple.</p>
<p>This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
Or read it <a href="http://www.gnu.org/licenses/licenses.html#GPL">online</a></p>
