import { cn } from '@/utils/classNames'
import { useState } from 'react'

export default function FileInput({
  label,
  className,
  type,
  onJSONChange,
  ...props
}: {
  label: string
  onJSONChange: (json: string) => void
} & React.InputHTMLAttributes<HTMLInputElement>) {
  const [currentFile, setFile] = useState<File | undefined>(undefined)

  const changeFile = (file: File | undefined) => {
    if (file === undefined) return
    setFile(file)
    const reader = new FileReader()
    reader.onload = (event) =>
      onJSONChange((event.target?.result as string) ?? '')
    reader.readAsText(file)
  }

  return (
    <div className="flex w-full flex-row">
      <label
        className={cn(
          'flex w-full cursor-pointer items-center justify-center rounded-lg border-2 border-primary-400 bg-primary-500/50 p-3 font-medium leading-none transition-colors hover:bg-primary-500 focus:border-white focus:outline-none',
          className
        )}
      >
        {currentFile
          ? `${currentFile.name} ${currentFile.size.toLocaleString('en', {
              currency: 'usd',
            })} KB`
          : label}
        <input
          type={'file'}
          className="absolute hidden w-full bg-transparent"
          {...props}
          onChange={(e) => changeFile(e.target.files?.[0])}
        />
      </label>
    </div>
  )
}
