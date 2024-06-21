import Close from '@/icons/Close'
import Maximize from '@/icons/Maximize'

export default function Controls({
  centerView,
  zoomIn,
  zoomOut,
}: {
  centerView: () => void
  zoomIn: () => void
  zoomOut: () => void
}) {
  return (
    <div className="absolute left-6 top-28 z-20 flex gap-2 rounded-md bg-black/40 p-1 backdrop-blur-lg">
      <button aria-label='Center view' onClick={centerView} className="h-8 w-8 rounded p-1 transition-colors hover:bg-white/10">
        <Maximize className="h-full w-full" />
      </button>
      <button aria-label='Zoom in' onClick={zoomIn} className="h-8 w-8 rounded p-2.5 transition-colors hover:bg-white/10">
        <Close className="h-full w-full rotate-45 fill-white" />
      </button>
      <button aria-label='Zoom out' onClick={zoomOut} className="h-8 w-8 rounded p-2 transition-colors hover:bg-white/10">
        <span className="h-0.5 w-full rounded bg-white" />
      </button>
    </div>
  )
}
