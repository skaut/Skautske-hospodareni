// TypeScript 6 (TS2882) rejects side-effect imports of modules without type
// declarations. The following modules are imported purely for their side
// effects and ship no types, so declare them as empty modules.
declare module '*.scss';
declare module 'moment/locale/*';
