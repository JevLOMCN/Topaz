import { MetaFn } from './overflow-store.js';
import { Ref } from 'vue';
export declare function useDocumentOverflowLockedEffect(doc: Ref<Document | null>, shouldBeLocked: Ref<boolean>, meta: MetaFn): import("vue").ComputedRef<boolean>;
