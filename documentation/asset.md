Asset Component
===

The asset component manages the creation of static resources inside sq
applications. Assets allow static resources to be part of framework and module
code and built into the web root. They also allow versioning of static resource
paths to break browser caching.

Assets are stored in the framework, module or application `assets/` directory
and built into the web root of the application in a folder generated from the
revision number.

When the framework debug mode is enabled the assets will be rebuilt every
request. Otherwise they will only be rebuilt when the revision option is
incremented.

Example
---

	// Asset returns the built asset path when used as a string
	$asset = sq::asset('my-scripts.js');
	
	// Asset returns the built asset path when used as a string
	view::script($asset);

### constructor

	asset sq::asset(string assetPath [, array options])

#### assetPath
Path of the asset to be loaded from the `assets/` directory. The `assets/`
directories are searched in the usual loading precedence. Application first,
then modules, then the framework core.

#### options
Component options

`int revision : 1` - This marker is coded into built asset path. It may be
incremented to break browser caches or force rebuilds.

`int permissions : 0777` - File permissions for the `built/` directory and asset
files.

`string path : built` - Path assets are built to.

### check

	public bool asset::check()

Returns true if the asset file exists, false otherwise.

### build

	public self asset::build()

Builds or rebuilds the asset.

### render

	public string asset::render()

Returns the path of the current built asset.