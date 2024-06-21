import Caret from '@/icons/Caret'
import * as Label from '@radix-ui/react-label'
import * as CSelect from '@radix-ui/react-select'

export default function Select({
  label,
  items,
  ...props
}: {
  label: string
  items: Array<{ value: string; label: string }>
} & CSelect.SelectProps) {
  return (
    <CSelect.Root {...props}>
      <Label.Root className="mb-4 text-base font-bold text-white">
        {label}
      </Label.Root>

      <CSelect.Trigger className="flex w-max gap-3 rounded-md border border-primary-500 bg-primary-600 px-3 py-2 font-normal text-white">
        <CSelect.Value />
        <CSelect.Icon>
          <Caret className="h-3 w-3" />
        </CSelect.Icon>
      </CSelect.Trigger>

      <CSelect.Portal>
        <CSelect.Content
          position="popper"
          sideOffset={12}
          className="z-[60] flex h-[--radix-select-content-trigger-height] w-[--radix-select-trigger-width] flex-col gap-2 overflow-auto rounded-md border border-primary-500 bg-primary-600 p-1 shadow-md"
        >
          <CSelect.Viewport>
            {items.map(({ label, value }) => (
              <CSelect.Item
                className="cursor-pointer rounded p-2 text-sm text-white transition-colors hover:bg-white/10"
                value={value}
                key={value}
              >
                <CSelect.ItemText>{label}</CSelect.ItemText>
              </CSelect.Item>
            ))}
          </CSelect.Viewport>
        </CSelect.Content>
      </CSelect.Portal>
    </CSelect.Root>
  )
}
