export default function Maximize(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width={20}
      height={20}
      viewBox="0 0 20 20"
      fill="none"
      {...props}
    >
      <path
        fill="#ffffff"
        d="M4.5 3A1.5 1.5 0 0 0 3 4.5V7a1 1 0 0 0 2 0V5h2a1 1 0 0 0 0-2H4.5ZM3 15.5A1.5 1.5 0 0 0 4.5 17H7a1 1 0 1 0 0-2H5v-2a1 1 0 1 0-2 0v2.5ZM17 15.5a1.5 1.5 0 0 1-1.5 1.5H13a1 1 0 1 1 0-2h2v-2a1 1 0 1 1 2 0v2.5ZM17 4.5A1.5 1.5 0 0 0 15.5 3H13a1 1 0 1 0 0 2h2v2a1 1 0 1 0 2 0V4.5Z"
      />
    </svg>
  )
}
