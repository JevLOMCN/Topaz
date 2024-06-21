import { FilterFn } from './features/Filters';
export declare const filterFns: {
    includesString: FilterFn<any>;
    includesStringSensitive: FilterFn<any>;
    equalsString: FilterFn<any>;
    arrIncludes: FilterFn<any>;
    arrIncludesAll: FilterFn<any>;
    arrIncludesSome: FilterFn<any>;
    equals: FilterFn<any>;
    weakEquals: FilterFn<any>;
    inNumberRange: FilterFn<any>;
};
export type BuiltInFilterFn = keyof typeof filterFns;
