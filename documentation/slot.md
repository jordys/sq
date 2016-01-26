Slot Component
===

Slots are small pieces of content that can be dropped in to views. They are
stored in the sq_slots model. Adding a slot in code automatically creates the
matching model record.

Slot content may placeholders to be replaced with passed in content. values to 
be replaced. By default the only value is `{base}` for the application base url.
More values may be passed in with the `replace()` method.

My default there are 3 types of content slots: markdown (default), text and
image. More slot types my be created by extending the sqSlot component and
creating a static method with the same name that returns the formatted slot
content.

Example
---

	// Create and render a slot
	echo sq::slot('my-slot', 'Example Content Slot')->replace([
		'username' => 'Jason Borne'
	]);

Properties
---

`string slot` - Slots model used by the component. Serves as a jumping off point
to modify slot content.

Methods
---

### constructor

	slot sq::slot(string id, string name [, array options])

#### id
Unique id of the content slot.

#### name
Friendly name of the content slot.

#### options
Component options:

`array replacers : []` - Names and values to replace matching placeholders in
the slot content.

`string type : markdown` - Content slot type.

`string content : null` - Default content of the slot in none is created in the
model.

### replace

	public self slot::replace(array replacers)

Accepts names and values that replace placeholders in the slot content.

#### replacers
Key / value array of content to added to the component to fill placeholders.

### render

	public string slot::render()

Returns the slot content with the placeholder values filled. The content is
wrapped in a `<div>` with the slot's id as a html class name.

Slot Type Methods
---

Each of these methods is responsible for outputing a specific type of content
slot. More of these methods may be added to add new content types by extending
the slot component.

### markdown

	public static string slot::markdown(model slot)

Returns slot content parsed from markdown source.

#### model
Model record of the slot to output.

### text

	public static string slot::text(model slot)

Returns the slot content with no processing.

#### model
Model record of the slot to output.

### image

	public static string slot::image(model slot)

Returns an html `<img>` tag. The souce url is the slot content.

#### model
Model record of the slot to output.