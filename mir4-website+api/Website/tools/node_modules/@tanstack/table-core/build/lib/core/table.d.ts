import { RequiredKeys } from '../utils';
import { Updater, TableOptionsResolved, TableState, Table, InitialTableState, Row, Column, RowModel, ColumnDef, TableOptions, RowData, TableMeta } from '../types';
export interface TableFeature {
    getDefaultOptions?: (table: any) => any;
    getInitialState?: (initialState?: InitialTableState) => any;
    createTable?: (table: any) => any;
    getDefaultColumnDef?: () => any;
    createColumn?: (column: any, table: any) => any;
    createHeader?: (column: any, table: any) => any;
    createCell?: (cell: any, column: any, row: any, table: any) => any;
    createRow?: (row: any, table: any) => any;
}
export interface CoreTableState {
}
export interface CoreOptions<TData extends RowData> {
    data: TData[];
    state: Partial<TableState>;
    onStateChange: (updater: Updater<TableState>) => void;
    debugAll?: boolean;
    debugTable?: boolean;
    debugHeaders?: boolean;
    debugColumns?: boolean;
    debugRows?: boolean;
    initialState?: InitialTableState;
    autoResetAll?: boolean;
    mergeOptions?: (defaultOptions: TableOptions<TData>, options: Partial<TableOptions<TData>>) => TableOptions<TData>;
    meta?: TableMeta<TData>;
    getCoreRowModel: (table: Table<any>) => () => RowModel<any>;
    getSubRows?: (originalRow: TData, index: number) => undefined | TData[];
    getRowId?: (originalRow: TData, index: number, parent?: Row<TData>) => string;
    columns: ColumnDef<TData, any>[];
    defaultColumn?: Partial<ColumnDef<TData, unknown>>;
    renderFallbackValue: any;
}
export interface CoreInstance<TData extends RowData> {
    initialState: TableState;
    reset: () => void;
    options: RequiredKeys<TableOptionsResolved<TData>, 'state'>;
    setOptions: (newOptions: Updater<TableOptionsResolved<TData>>) => void;
    getState: () => TableState;
    setState: (updater: Updater<TableState>) => void;
    _features: readonly TableFeature[];
    _queue: (cb: () => void) => void;
    _getRowId: (_: TData, index: number, parent?: Row<TData>) => string;
    getCoreRowModel: () => RowModel<TData>;
    _getCoreRowModel?: () => RowModel<TData>;
    getRowModel: () => RowModel<TData>;
    getRow: (id: string) => Row<TData>;
    _getDefaultColumnDef: () => Partial<ColumnDef<TData, unknown>>;
    _getColumnDefs: () => ColumnDef<TData, unknown>[];
    _getAllFlatColumnsById: () => Record<string, Column<TData, unknown>>;
    getAllColumns: () => Column<TData, unknown>[];
    getAllFlatColumns: () => Column<TData, unknown>[];
    getAllLeafColumns: () => Column<TData, unknown>[];
    getColumn: (columnId: string) => Column<TData, unknown> | undefined;
}
export declare function createTable<TData extends RowData>(options: TableOptionsResolved<TData>): Table<TData>;
