import { AccessorFn, ColumnDef, DisplayColumnDef, GroupColumnDef, IdentifiedColumnDef, RowData } from './types';
import { DeepKeys, DeepValue } from './utils';
export type ColumnHelper<TData extends RowData> = {
    accessor: <TAccessor extends AccessorFn<TData> | DeepKeys<TData>, TValue extends TAccessor extends AccessorFn<TData, infer TReturn> ? TReturn : TAccessor extends DeepKeys<TData> ? DeepValue<TData, TAccessor> : never>(accessor: TAccessor, column: TAccessor extends AccessorFn<TData> ? DisplayColumnDef<TData, TValue> : IdentifiedColumnDef<TData, TValue>) => ColumnDef<TData, TValue>;
    display: (column: DisplayColumnDef<TData>) => ColumnDef<TData, unknown>;
    group: (column: GroupColumnDef<TData>) => ColumnDef<TData, unknown>;
};
export declare function createColumnHelper<TData extends RowData>(): ColumnHelper<TData>;
