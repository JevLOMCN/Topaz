import { InventoryAtom } from '@/atoms/Inventory'
import Reset from '@/icons/Reset'
import { useSetAtom } from 'jotai'
import { useTranslation } from '../../../public/locales/client'
import ConfirmationDialog from '../shared/ConfirmationDialog'
import { defaultInventoryValue } from '@/utils/craftingCalculator'

export default function ResetInventory() {
  const { t } = useTranslation()
  const setInventory = useSetAtom(InventoryAtom)

  return (
    <ConfirmationDialog.Wrapper>
      <ConfirmationDialog.Trigger className="h-12 w-12 rounded-md transition-colors hover:bg-gray-100/10">
        <Reset className="fill-white" />
      </ConfirmationDialog.Trigger>
      <ConfirmationDialog.Content className="flex flex-col gap-4">
        <ConfirmationDialog.Title className="text-2xl font-bold text-white">
          {t('Are you sure?')}
        </ConfirmationDialog.Title>
        <ConfirmationDialog.Description className="font-normal text-white">
          {t(
            'This action is irreversible and will result in the loss of all your items.'
          )}
        </ConfirmationDialog.Description>

        <div className="flex justify-end gap-[25px]">
          <ConfirmationDialog.Cancel className="rounded bg-primary-400/20 px-4 py-2 text-white transition-colors hover:bg-primary-400/40">
            {t('Cancel')}
          </ConfirmationDialog.Cancel>
          <ConfirmationDialog.Action
            onClick={() => setInventory(defaultInventoryValue)}
            className="rounded bg-red-100 px-4 py-2 font-medium text-red-500 transition-colors hover:bg-red-200"
          >
            {t('Yes, reset my inventory')}
          </ConfirmationDialog.Action>
        </div>
      </ConfirmationDialog.Content>
    </ConfirmationDialog.Wrapper>
  )
}
