/**
 * To make your IDE aware that Ziggy's route() helper is available globally, 
 * and to type it correctly, add a declaration like this in a .d.ts file somewhere in your project:
 */
import { route as routeFn } from 'ziggy-js';

declare global {
    var route: typeof routeFn;
}

/**
 * If you're using TypeScript, you may need to add the following declaration to a .d.ts file in your project 
 * to avoid type errors when using the route() function in your Vue component templates:
 */
declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof routeFn;
    }
}

/**
 * Strict route name type checking
 * By default, even when you generate type definitions to enable better autocompletion, 
 * Ziggy still allows passing any string to route(). You can optionally enable strict type checking of route names, 
 * so that calling route() with a route name Ziggy doensn't recognizes triggers a type error. 
 * To do so, extend Ziggy's TypeConfig interface and set strictRouteNames to true:
 */
/* declare module 'ziggy-js' {
    interface TypeConfig {
        strictRouteNames: true
    }
}

export { }; */