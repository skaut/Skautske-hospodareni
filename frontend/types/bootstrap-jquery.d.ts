// @types/bootstrap references the global JQuery interface even when Bootstrap
// is consumed without jQuery. The application does not load jQuery; this empty
// interface only satisfies those optional plugin declarations.
interface JQuery {}
