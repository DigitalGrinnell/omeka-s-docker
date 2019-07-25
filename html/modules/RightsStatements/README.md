# Rights Statements

An Omeka S module to allow users to easily select rights statements from
[RightsStatements.org](http://rightsstatements.org).

The module adds a "Rights Statement" data type. To use it, assign it as the
data type for a Property (i.e., Dublin Core Rights) for a Resource Template.
The pre-installed Base Resource template already includes the Rights property, so a simple option is to just set the data type there.

When the Rights Statement type is used on a property, the UI for that
property becomes a drop-down with the 11 RightsStatements.org statements
for the user to select. Both the human-readable name and the linked data URI
for the selected statement are applied to the item.
