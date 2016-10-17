Simple Steps for the Featured Popup:

1) Upload the content of the part-1 directory to your Magento store root.
2) Upload the content of the part-2 directory to your Magento store root.
3) Add the code to your cms/catalog/xml file layout update:

	<reference name="after_body_start">
		<block type="featuredpopup/featuredpopup" name="toogasfeaturedpopup"
		as="toogasfeaturedpopup"
		template="toogas_featuredpopup/featuredpopup.phtml" />
	</reference>


   Example for the homepage:
	a) Go to Cms -> Pages
	b) Open your Cms Page
	c) Select the tab Design
	d) Paste the code on the Layout Update XML text area.
	e) Save your Cms Page

4) You may need to refresh your cache.

5) That's all. Please contact us if you need support: http://www.toogas.com
