export default function Info(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width={20}
      height={20}
      fill="none"
      viewBox="0 0 20 20"
      {...props}
    >
      <g clipPath="url(#a)">
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M10 20c5.514 0 10-4.486 10-10S15.514 0 10 0 0 4.486 0 10s4.486 10 10 10Zm1-6a1 1 0 1 1-2 0v-4a1 1 0 1 1 2 0v4Zm-1-9a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"
          clipRule="evenodd"
        />
      </g>
      <defs>
        <clipPath id="a">
          <path fill="#fff" d="M0 0h20v20H0z" />
        </clipPath>
      </defs>
    </svg>
  )
}
