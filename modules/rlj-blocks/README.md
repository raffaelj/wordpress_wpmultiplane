# rlj-blocks

Experimental WordPress Plugin to provide a wrapper block with a background image, a video link block and an extension for image titles

I called the block "Section". It is only a wrapper block, that contains other blocks, but with the ability to add a background image. It adds a handful of classes and adds one style attribute if the section has a background image.

The CSS will not load automatically in the frontend. You have to add your own custom styles. It's a bad design decision, to run tons of requests just to add 10 lines of css for each plugin. I use it with a child theme of Twentynineteen.

The video link block requires the unreleased and legacy addon rlj-wp-utils or the experimental plugin WPMultiplane. I'll fix that later...

The image title extensions are experimental.

## Installation

Copy all files of this repository into `/wp-content/plugins/rlj-blocks`.

I don't know, how to add a plugin to the official WordPress plugins, yet.

## Notes

If you use the new, fancy Gutenberg features, Wordpress will try to load a Webfont from Google. They still default to kick their users privacy straight into the ass. So dont't forget to install [Disable Google Fonts](https://wordpress.org/plugins/disable-google-fonts/).

## Custom CSS

Add this code to your child theme or copy it to "Additional CSS" of your Customizer.

```css
.has-background {
  background-size: cover;
  background-position: 50%;
  height: 100%; }

.has-parallax {
  background-attachment: fixed; }

.is-style-rounded {
  border-radius: 2px; }

.is-style-shadow {
  -webkit-box-shadow: 0 3px 4px 0px rgba(0, 0, 0, 0.2);
  box-shadow: 0 3px 4px 0px rgba(0, 0, 0, 0.2); }

.is-style-shadow-rounded {
  border-radius: 2px;
  -webkit-box-shadow: 0 3px 4px 0px rgba(0, 0, 0, 0.2);
  box-shadow: 0 3px 4px 0px rgba(0, 0, 0, 0.2); }
```

## Trivia

I started to discover the new Gutenberg blocks from WordPress and I missed one very important feature: A simple `<div>` with background images. I tried multiple plugins, but all of them

* had too much useless features,
* added jQuery and didn't work without Javascript,
* were buggy here and there
* ...

After reading and testing multiple tutorials to build custem Gutenberg blocks, I discovered [Create Guten Block](https://github.com/ahmadawais/create-guten-block). I should have used this in the first place. Without any knowledge about Gutenberg, React or ESNext, I was able to built this plugin with a lot of trials and errors, but now it works.

## Built with Create Guten Block

This project was bootstrapped with [Create Guten Block](https://github.com/ahmadawais/create-guten-block) ([@MrAhmadAwais](https://twitter.com/mrahmadawais/).

Below you will find some information on how to run scripts.

>You can find the most recent version of this guide [here](https://github.com/ahmadawais/create-guten-block).

### `npm start`
- Use to compile and run the block in development mode.
- Watches for any changes and reports back any errors in your code.

### `npm run build`
- Use to build production code for your block inside `dist` folder.
- Runs once and reports back the gzip file sizes of the produced code.

### `npm run eject`
- Use to eject your plugin out of `create-guten-block`.
- Provides all the configurations so you can customize the project as you want.
- It's a one-way street, `eject` and you have to maintain everything yourself.
- You don't normally have to `eject` a project because by ejecting you lose the connection with `create-guten-block` and from there onwards you have to update and maintain all the dependencies on your own.
