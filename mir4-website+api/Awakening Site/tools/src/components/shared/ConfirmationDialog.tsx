import { cn } from '@/utils/classNames'
import * as AlertDialog from '@radix-ui/react-alert-dialog'

function Wrapper(props: AlertDialog.AlertDialogProps) {
  return <AlertDialog.Root {...props} />
}

function Trigger(props: AlertDialog.AlertDialogTriggerProps) {
  return <AlertDialog.Trigger {...props} />
}

function Content({ className, ...props }: AlertDialog.AlertDialogContentProps) {
  return (
    <AlertDialog.Portal>
      <AlertDialog.Overlay className="fixed inset-0 z-50 bg-black/40 data-[state=closed]:animate-contentHide data-[state=open]:animate-contentShow" />
      <AlertDialog.Content
        className={cn(
          'fixed left-[50%] top-[50%] z-50 flex h-max max-h-[85vh] w-full max-w-xl translate-x-[-50%] translate-y-[-50%] flex-col overflow-auto rounded-lg border border-primary-500 bg-primary-600 px-4 py-3 font-main focus:outline-none data-[state=closed]:animate-contentHide data-[state=open]:animate-contentShow',
          className
        )}
        {...props}
      />
    </AlertDialog.Portal>
  )
}

const ConfirmationDialog = {
  Wrapper,
  Content,
  Trigger,
  Title: AlertDialog.Title,
  Description: AlertDialog.Description,
  Cancel: AlertDialog.Cancel,
  Action: AlertDialog.Action,
}

export default ConfirmationDialog
