import Modal from '@/components/shared/Modal'
import Settings from '@/icons/Settings'
import SettingsModal from './SettingsModal'

export default function ManageSettings() {
  return (
    <Modal.Wrapper>
      <Modal.Trigger
        aria-label="Change settings"
        className="flex h-[3.25rem] w-[3.25rem] shrink-0 rounded-full border-2 border-transparent bg-black/20 p-2 outline-none transition-colors hover:border-white/10"
      >
        <Settings className="h-6 w-6 fill-white" />
      </Modal.Trigger>
      <Modal.Content className="rounded border border-white/10 bg-primary-400/5 p-4 flex flex-col max-w-3xl gap-4 pb-6 backdrop-blur-lg data-[state=closed]:animate-contentHide data-[state=open]:animate-contentShow">
        <SettingsModal />
      </Modal.Content>
    </Modal.Wrapper>
  )
}
