import { RowModel } from '..';
import { TableFeature } from '../core/table';
import { BuiltInFilterFn } from '../filterFns';
import { Column, OnChangeFn, Table, Row, Updater, RowData, FilterMeta, FilterFns } from '../types';
export interface FiltersTableState {
    columnFilters: ColumnFiltersState;
    globalFilter: any;
}
export type ColumnFiltersState = ColumnFilter[];
export interface ColumnFilter {
    id: string;
    value: unknown;
}
export interface ResolvedColumnFilter<TData extends RowData> {
    id: string;
    resolvedValue: unknown;
    filterFn: FilterFn<TData>;
}
export interface FilterFn<TData extends RowData> {
    (row: Row<TData>, columnId: string, filterValue: any, addMeta: (meta: FilterMeta) => void): boolean;
    resolveFilterValue?: TransformFilterValueFn<TData>;
    autoRemove?: ColumnFilterAutoRemoveTestFn<TData>;
}
export type TransformFilterValueFn<TData extends RowData> = (value: any, column?: Column<TData, unknown>) => unknown;
export type ColumnFilterAutoRemoveTestFn<TData extends RowData> = (value: any, column?: Column<TData, unknown>) => boolean;
export type CustomFilterFns<TData extends RowData> = Record<string, FilterFn<TData>>;
export type FilterFnOption<TData extends RowData> = 'auto' | BuiltInFilterFn | keyof FilterFns | FilterFn<TData>;
export interface FiltersColumnDef<TData extends RowData> {
    filterFn?: FilterFnOption<TData>;
    enableColumnFilter?: boolean;
    enableGlobalFilter?: boolean;
}
export interface FiltersColumn<TData extends RowData> {
    getAutoFilterFn: () => FilterFn<TData> | undefined;
    getFilterFn: () => FilterFn<TData> | undefined;
    setFilterValue: (updater: Updater<any>) => void;
    getCanFilter: () => boolean;
    getCanGlobalFilter: () => boolean;
    getFacetedRowModel: () => RowModel<TData>;
    _getFacetedRowModel?: () => RowModel<TData>;
    getIsFiltered: () => boolean;
    getFilterValue: () => unknown;
    getFilterIndex: () => number;
    getFacetedUniqueValues: () => Map<any, number>;
    _getFacetedUniqueValues?: () => Map<any, number>;
    getFacetedMinMaxValues: () => undefined | [number, number];
    _getFacetedMinMaxValues?: () => undefined | [number, number];
}
export interface FiltersRow<TData extends RowData> {
    columnFilters: Record<string, boolean>;
    columnFiltersMeta: Record<string, FilterMeta>;
}
interface FiltersOptionsBase<TData extends RowData> {
    enableFilters?: boolean;
    manualFiltering?: boolean;
    filterFromLeafRows?: boolean;
    maxLeafRowFilterDepth?: number;
    getFilteredRowModel?: (table: Table<any>) => () => RowModel<any>;
    onColumnFiltersChange?: OnChangeFn<ColumnFiltersState>;
    enableColumnFilters?: boolean;
    globalFilterFn?: FilterFnOption<TData>;
    onGlobalFilterChange?: OnChangeFn<any>;
    enableGlobalFilter?: boolean;
    getColumnCanGlobalFilter?: (column: Column<TData, unknown>) => boolean;
    getFacetedRowModel?: (table: Table<TData>, columnId: string) => () => RowModel<TData>;
    getFacetedUniqueValues?: (table: Table<TData>, columnId: string) => () => Map<any, number>;
    getFacetedMinMaxValues?: (table: Table<TData>, columnId: string) => () => undefined | [number, number];
}
type ResolvedFilterFns = keyof FilterFns extends never ? {
    filterFns?: Record<string, FilterFn<any>>;
} : {
    filterFns: Record<keyof FilterFns, FilterFn<any>>;
};
export interface FiltersOptions<TData extends RowData> extends FiltersOptionsBase<TData>, ResolvedFilterFns {
}
export interface FiltersInstance<TData extends RowData> {
    setColumnFilters: (updater: Updater<ColumnFiltersState>) => void;
    resetColumnFilters: (defaultState?: boolean) => void;
    getPreFilteredRowModel: () => RowModel<TData>;
    getFilteredRowModel: () => RowModel<TData>;
    _getFilteredRowModel?: () => RowModel<TData>;
    setGlobalFilter: (updater: Updater<any>) => void;
    resetGlobalFilter: (defaultState?: boolean) => void;
    getGlobalAutoFilterFn: () => FilterFn<TData> | undefined;
    getGlobalFilterFn: () => FilterFn<TData> | undefined;
    getGlobalFacetedRowModel: () => RowModel<TData>;
    _getGlobalFacetedRowModel?: () => RowModel<TData>;
    getGlobalFacetedUniqueValues: () => Map<any, number>;
    _getGlobalFacetedUniqueValues?: () => Map<any, number>;
    getGlobalFacetedMinMaxValues: () => undefined | [number, number];
    _getGlobalFacetedMinMaxValues?: () => undefined | [number, number];
}
export declare const Filters: TableFeature;
export declare function shouldAutoRemoveFilter<TData extends RowData>(filterFn?: FilterFn<TData>, value?: any, column?: Column<TData, unknown>): boolean;
export {};
