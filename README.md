PressBooks Textbook Modified
===================
Theme modifications by Jack Dougherty
Brad Payne created the original plugin (https://github.com/BCcampus/pressbooks-textbook), but since it does not permit a child-theme, I forked his repo and modified selected files. The goal is to increase the size of author bylines and TOC, and to produce color content (for web/epub editions) vs. black and white content (for pdf edition).

##Created new files

- this README.md file
- pressbooks-textbook-modified.php (in place of pressbooks-textbook.php)
- screenshot-modified.png (in place of screenshot.png in theme folder)

##Support divs to display content for different editions
To display color images and "click" captions for web/epub editions, insert this in text editor:
```
<div class="not-pdf">
[insert color image and click caption]
</div>
<div class="pdf-only">
[insert b&w image and no-click caption]
</div>
```
Make sure relevant CSS snippets appear in each of three style.css files below.

## style.css (in theme folder for WEB edition for online reading)
```
/** support divs to export content for web-only vs not-web; pdf-only vs not-pdf **/
.not-web, .pdf-only {
		display: none;
}
```
Increase font-size of author's name at top of each chapter to 1 em
```
.chapter_author {
	font-family: "Crimson","Times New Roman", serif;
	font-style: italic;
	font-weight: normal;
	font-size: 1em; /*Jack modified*/
	margin: -70px 0 100px;
}
```

## export/epub/style.css (in theme folder for EPUB and MOBI export)
```
/** support divs to export content for web-only vs not-web; pdf-only vs not-pdf **/
.not-web, .pdf-only {
		display: none;
}
```

## export/prince/style.css  (in theme folder for PDF export)
```
/** support divs to export content for web-only vs not-web; pdf-only vs not-pdf **/
.web-only, .not-pdf {
		display: none;
}
```

Increase font-size of author's name at top of each chapter from 9pt to 10pt
```
div.ugc h2.chapter-author {
	font: italic 10pt "Crimson","Times New Roman", serif; /*Jack Modified*/
}
```

Increase font-size of Table of Contents text from 9pt to 11pt
```
/* TABLE OF CONTENTS
======================= */
#toc {
	page-break-before: right;
	counter-reset: part;
	font-size: 11pt; /*Jack Modified*/
}
```
Replace blue color of links in body text with black color
```
a {
	color: black; /*Jack Modified*/
	text-decoration: none;
}
```
